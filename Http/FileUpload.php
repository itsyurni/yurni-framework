<?php
namespace yurni\Http;

use yurni\Exception\RuntimeException;

/**
 * FileUpload
 *
 * Wraps a single uploaded file from $_FILES.
 *
 * Usage:
 *   $file = $request->file('avatar');
 *
 *   if ($file->isValid()) {
 *       $file->validate(['image/jpeg', 'image/png'], maxSizeKB: 2048)
 *            ->move('public/uploads');
 *   }
 */
class FileUpload {

    private string $name;
    private string $extension;
    private string $originalName;
    private string $tmp;
    private int    $size;
    private int    $error;
    private string $mimeType;
    private string $location;

    public function __construct(array $file) {
        $this->tmp          = $file['tmp_name'];
        $this->size         = (int) $file['size'];
        $this->error        = (int) $file['error'];
        $this->originalName = $file['name'];
        $this->name         = pathinfo($file['name'], PATHINFO_FILENAME);
        $this->extension    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $this->location     = $this->tmp;
        $this->mimeType     = $this->detectMime($file);
    }

    // -------------------------------------------------------------------------
    //  Status
    // -------------------------------------------------------------------------

    /** Returns true if the file was uploaded without errors. */
    public function isValid(): bool {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmp);
    }

    /** PHP upload error code (0 = no error). */
    public function getError(): int { return $this->error; }

    /** Human-readable error message. */
    public function getErrorMessage(): string {
        return match ($this->error) {
            UPLOAD_ERR_OK         => 'No error.',
            UPLOAD_ERR_INI_SIZE   => 'File exceeds the upload_max_filesize directive in php.ini.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
            default               => 'Unknown upload error.',
        };
    }

    // -------------------------------------------------------------------------
    //  Validate (chainable)
    // -------------------------------------------------------------------------

    /**
     * Assert that the file meets the given constraints.
     *
     * @param string[] $allowedMimes  e.g. ['image/jpeg', 'image/png']
     * @param int      $maxSizeKB     Maximum file size in kilobytes (0 = no limit)
     * @param string[] $allowedExts   e.g. ['jpg', 'png'] (optional, additional layer)
     *
     * @throws RuntimeException on validation failure
     */
    public function validate(array $allowedMimes = [], int $maxSizeKB = 0, array $allowedExts = []): static {
        if (!$this->isValid()) {
            throw new RuntimeException("Upload error: {$this->getErrorMessage()}");
        }

        if ($allowedMimes && !in_array($this->mimeType, $allowedMimes, true)) {
            throw new RuntimeException(
                "Invalid file type '{$this->mimeType}'. Allowed: " . implode(', ', $allowedMimes)
            );
        }

        if ($allowedExts && !in_array($this->extension, $allowedExts, true)) {
            throw new RuntimeException(
                "Invalid file extension '{$this->extension}'. Allowed: " . implode(', ', $allowedExts)
            );
        }

        if ($maxSizeKB > 0 && $this->getSizeKB() > $maxSizeKB) {
            throw new RuntimeException(
                "File is too large ({$this->getSizeKB()} KB). Maximum allowed: {$maxSizeKB} KB."
            );
        }

        return $this;
    }

    // -------------------------------------------------------------------------
    //  Move
    // -------------------------------------------------------------------------

    /**
     * Move the file to its final destination.
     *
     * @param string      $directory  Target directory
     * @param string|null $filename   New filename without extension (optional)
     *
     * @throws RuntimeException on failure
     */
    public function move(string $directory, ?string $filename = null): static {
        if (!$this->isValid()) {
            throw new RuntimeException("Cannot move file: {$this->getErrorMessage()}");
        }

        $directory = rtrim($directory, '/\\');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        if (!is_writable($directory)) {
            throw new RuntimeException("Upload directory '{$directory}' is not writable.");
        }

        if ($filename !== null) {
            $this->name = pathinfo($filename, PATHINFO_FILENAME);
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if ($ext) {
                $this->extension = strtolower($ext);
            }
        }

        $destination = $directory . DIRECTORY_SEPARATOR . $this->getFilename();

        if (!move_uploaded_file($this->tmp, $destination)) {
            throw new RuntimeException("Failed to move uploaded file to '{$destination}'.");
        }

        $this->location = $destination;
        return $this;
    }

    /**
     * Move and generate a unique filename automatically.
     *
     * @param string $directory Target directory
     */
    public function moveWithUniqueName(string $directory): static {
        return $this->move($directory, uniqid('', true));
    }

    // -------------------------------------------------------------------------
    //  Getters
    // -------------------------------------------------------------------------

    public function getFilename(): string {
        $ext = $this->extension !== '' ? ".{$this->extension}" : '';
        return $this->name . $ext;
    }

    public function getOriginalName(): string   { return $this->originalName; }
    public function getExtension(): string      { return $this->extension; }
    public function getMimeType(): string       { return $this->mimeType; }
    public function getLocation(): string       { return $this->location; }
    public function getSizeBytes(): int         { return $this->size; }
    public function getSizeKB(): float          { return round($this->size / 1024, 2); }
    public function getSizeMB(): float          { return round($this->size / 1_048_576, 2); }
    public function getTempPath(): string       { return $this->tmp; }

    /** True if the file is an image (based on MIME). */
    public function isImage(): bool {
        return str_starts_with($this->mimeType, 'image/');
    }

    // -------------------------------------------------------------------------
    //  Internal
    // -------------------------------------------------------------------------

    private function detectMime(array $file): string {
        if (is_uploaded_file($this->tmp) && function_exists('finfo_open')) {
            return (new \finfo(FILEINFO_MIME_TYPE))->file($this->tmp);
        }
        return $file['type'] ?? 'application/octet-stream';
    }

    public function __toString(): string {
        return (string) file_get_contents($this->location);
    }
}
