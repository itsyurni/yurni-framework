<?php
namespace yurni\View;

/**
 * Template Engine
 *
 * Compiles template files into executable PHP code.
 * Supports: inheritance, blocks, conditions, loops, echoing, verbatim, and raw PHP.
 */
class Template
{

    private array $blocks = [];
    private array $verbatimBlocks = [];
    private string $temp_path;
    private string $cache_path;
    private bool $cache_enabled;
    private bool $optimize;

    public function __construct(array $data = [])
    {
        $this->temp_path = rtrim($data['temp_path'], '/\\') . DIRECTORY_SEPARATOR;
        $this->cache_path = rtrim($data['cache_path'], '/\\') . DIRECTORY_SEPARATOR;
        $this->cache_enabled = $data['cache'] ?? false;
        $this->optimize = $data['optimize'] ?? false;
    }

    // =========================================================================
    //  Public API
    // =========================================================================

    public function render(string $file, array $params = []): string
    {
        $this->blocks = [];

        $cached_file = $this->cache($file);

        extract($params, EXTR_SKIP);

        ob_start();
        require $cached_file;
        return ob_get_clean();
    }

    public function clearCache(): void
    {
        foreach (glob($this->cache_path . '*.php') as $file) {
            unlink($file);
        }
    }

    // =========================================================================
    //  Caching
    // =========================================================================

    public function cache(string $file): string
    {
        if (!is_dir($this->cache_path)) {
            mkdir($this->cache_path, 0744, true);
        }

        $file_with_ext = $this->ensureExtension($file);
        $source_file = $this->temp_path . $file_with_ext;
        $cached_file = $this->cache_path . md5($source_file) . '.php';

        $needsCompile = !$this->cache_enabled
            || !file_exists($cached_file)
            || filemtime($cached_file) < filemtime($source_file);

        if ($needsCompile) {
            $code = $this->includeFile($file);

            if ($this->optimize) {
                $code = $this->minify($code);
            }

            $code = $this->compile($code);

            file_put_contents(
                $cached_file,
                '<?php class_exists(\'' . __CLASS__ . '\') or exit; ?>' . PHP_EOL . $code
            );
        }

        return $cached_file;
    }

    // =========================================================================
    //  File Inclusion & Inheritance
    // =========================================================================

    public function includeFile(string $file): string
    {
        $file = trim($file, "'\"");
        $file_with_ext = $this->ensureExtension($file);

        $realBase = realpath($this->temp_path);
        $realFile = realpath($this->temp_path . $file_with_ext);

        if ($realBase === false) {
            throw new \RuntimeException("Templates directory not found: {$this->temp_path}");
        }

        if ($realFile === false || !str_starts_with($realFile, $realBase . DIRECTORY_SEPARATOR)) {
            throw new \RuntimeException("Access denied: '{$file}' is outside the templates directory.");
        }

        $code = file_get_contents($realFile);

        if (preg_match_all('/\{%\s*(extends|include)\s*(.+?)\s*\%}/is', $code, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $value) {
                $code = str_replace($value[0], $this->includeFile($value[2]), $code);
            }
        }

        $code = preg_replace('/{% ?(extends|include) ?\'?(.*?)\'? ?%}/i', '', $code);

        return $code;
    }

    // =========================================================================
    //  Compiler Pipeline
    // =========================================================================

    public function compile(string $output): string
    {
        $output = $this->compileVerbatim($output);
        $output = $this->compileBlock($output);
        $output = $this->compileYield($output);
        $output = $this->compileConditions($output);
        $output = $this->compileLoops($output);
        $output = $this->compileEcho($output);
        $output = $this->compileRawPhp($output);
        $output = $this->restoreVerbatim($output);
        return $output;
    }

    // =========================================================================
    //  Conditions  {% if %} / {% elseif %} / {% else %} / {% endif %}
    // =========================================================================

    private function compileConditions(string $output): string
    {
        // {% if (expr) %}
        $output = preg_replace(
            '/\{%\s*if\s*(.+?)\s*%\}/is',
            '<?php if ($1): ?>',
            $output
        );

        // {% elseif (expr) %} / {% else if (expr) %}
        $output = preg_replace(
            '/\{%\s*else\s*if\s*(.+?)\s*%\}/is',
            '<?php elseif ($1): ?>',
            $output
        );
        $output = preg_replace(
            '/\{%\s*elseif\s*(.+?)\s*%\}/is',
            '<?php elseif ($1): ?>',
            $output
        );

        // {% else %}
        $output = preg_replace(
            '/\{%\s*else\s*%\}/is',
            '<?php else: ?>',
            $output
        );

        // {% endif %}
        $output = preg_replace(
            '/\{%\s*endif\s*%\}/is',
            '<?php endif; ?>',
            $output
        );

        // {% unless (expr) %} ... {% endunless %}  (shortcut for if not)
        $output = preg_replace(
            '/\{%\s*unless\s*(.+?)\s*%\}/is',
            '<?php if (!($1)): ?>',
            $output
        );

        $output = preg_replace(
            '/\{%\s*endunless\s*%\}/is',
            '<?php endif; ?>',
            $output
        );

        return $output;
    }

    // =========================================================================
    //  Loops  {% for %} / {% foreach %} / {% while %} / {% each %}
    // =========================================================================

    private function compileLoops(string $output): string
    {
        // {% foreach $arr as $item %}  ...  {% endforeach %}
        $output = preg_replace(
            '/\{%\s*foreach\s*(.+?)\s+as\s+(.+?)\s*%\}/is',
            '<?php foreach ($1 as $2): ?>',
            $output
        );
        $output = preg_replace('/\{%\s*endforeach\s*%\}/is', '<?php endforeach; ?>', $output);

        // {% each $arr as $item %}  ...  {% endeach %}  (alias)
        $output = preg_replace(
            '/\{%\s*each\s*(.+?)\s+as\s+(.+?)\s*%\}/is',
            '<?php foreach ($1 as $2): ?>',
            $output
        );
        $output = preg_replace('/\{%\s*endeach\s*%\}/is', '<?php endforeach; ?>', $output);

        // {% for $i = 0; $i < 10; $i++ %}  ...  {% endfor %}
        $output = preg_replace(
            '/\{%\s*for\s*(.+?)\s*%\}/is',
            '<?php for ($1): ?>',
            $output
        );
        $output = preg_replace('/\{%\s*endfor\s*%\}/is', '<?php endfor; ?>', $output);

        // {% while (expr) %}  ...  {% endwhile %}
        $output = preg_replace(
            '/\{%\s*while\s*(.+?)\s*%\}/is',
            '<?php while ($1): ?>',
            $output
        );
        $output = preg_replace('/\{%\s*endwhile\s*%\}/is', '<?php endwhile; ?>', $output);

        return $output;
    }

    // =========================================================================
    //  Echo Directives
    // =========================================================================

    private function compileEcho(string $output): string
    {
        // {{{ var }}}  → htmlspecialchars (safe / escaped)
        $output = preg_replace(
            '/\{{{\s*(.+?)\s*\}}}/is',
            "<?php echo htmlspecialchars((string)($1), ENT_QUOTES, 'UTF-8'); ?>",
            $output
        );

        // {{ var }}  → raw echo
        $output = preg_replace(
            '/\{{\s*(.+?)\s*\}}/is',
            '<?php echo $1; ?>',
            $output
        );

        return $output;
    }

    // =========================================================================
    //  Raw PHP  {% ... %}
    // =========================================================================

    private function compileRawPhp(string $output): string
    {
        return preg_replace('/\{%\s*(.+?)\s*%\}/is', '<?php $1 ?>', $output);
    }

    // =========================================================================
    //  Block & Yield
    // =========================================================================

    public function compileBlock(string $code): string
    {
        if (
            preg_match_all(
                '/\{%\s*block\s+(?P<blockName>\S+?)\s*%\}(?P<blockContent>.*?)\{%\s*endblock\s*%\}/is',
                $code,
                $matches,
                PREG_SET_ORDER
            )
        ) {
            foreach ($matches as $value) {
                $name = $value['blockName'];
                $content = $value['blockContent'];

                if (!isset($this->blocks[$name])) {
                    $this->blocks[$name] = '';
                }

                $this->blocks[$name] = str_contains($content, '@parent')
                    ? str_replace('@parent', $this->blocks[$name], $content)
                    : $content;

                $code = str_replace($value[0], '', $code);
            }
        }

        return $code;
    }

    public function compileYield(string $code): string
    {
        foreach ($this->blocks as $block => $value) {
            $code = preg_replace('/\{%\s*yield\s+' . preg_quote($block, '/') . '\s*%\}/', $value, $code);
        }

        return preg_replace('/\{%\s*yield\s+.*?\s*%\}/i', '', $code);
    }

    // =========================================================================
    //  Verbatim  {% verbatim %} ... {% endverbatim %}
    // =========================================================================

    private function compileVerbatim(string $code): string
    {
        $this->verbatimBlocks = [];

        return preg_replace_callback(
            '/\{%\s*verbatim\s*%\}(.*?)\{%\s*endverbatim\s*%\}/is',
            function ($matches) {
                $placeholder = '__VERBATIM_' . count($this->verbatimBlocks) . '__';
                $this->verbatimBlocks[$placeholder] = $matches[1];
                return $placeholder;
            },
            $code
        );
    }

    private function restoreVerbatim(string $code): string
    {
        foreach ($this->verbatimBlocks as $placeholder => $content) {
            $code = str_replace($placeholder, $content, $code);
        }
        return $code;
    }

    // =========================================================================
    //  Helpers
    // =========================================================================

    private function ensureExtension(string $file): string
    {
        return str_ends_with($file, '.php') ? $file : $file . '.php';
    }

    private function minify(string $code): string
    {
        $preserved = [];

        $code = preg_replace_callback(
            '/<(pre|textarea|script)\b[^>]*>.*?<\/\1>/is',
            function ($matches) use (&$preserved) {
                $key = '__PRESERVED_' . count($preserved) . '__';
                $preserved[$key] = $matches[0];
                return $key;
            },
            $code
        );

        $code = trim(preg_replace('/[ \t]+/', ' ', $code));
        $code = preg_replace('/\n\s*\n/', "\n", $code);

        foreach ($preserved as $key => $value) {
            $code = str_replace($key, $value, $code);
        }

        return $code;
    }
}