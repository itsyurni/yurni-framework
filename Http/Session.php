<?php
namespace yurni\Http;

/**
 * Session Manager
 *
 * Simple wrapper around PHP sessions with flash message support.
 *
 * Usage:
 *   $session = new Session();
 *   $session->set('user_id', 1);
 *   $session->get('user_id');         // 1
 *   $session->has('user_id');         // true
 *   $session->remove('user_id');
 *   $session->flash('success', 'Done!');
 *   $session->getFlash('success');    // 'Done!'
 *   $session->destroy();
 */
class Session
{

    private const FLASH_KEY = '_flash';

    /**
     * يضمن أن tickFlash() تُنفَّذ مرة واحدة فقط طوال دورة حياة الطلب،
     * بغض النظر عن عدد مرات إنشاء new Session().
     */
    private static bool $ticked = false;

    public function __construct()
    {
        $this->start();

        if (!self::$ticked) {
            $this->tickFlash();
            self::$ticked = true;
        }
    }

    // -------------------------------------------------------------------------
    //  Core: get / set / has / remove
    // -------------------------------------------------------------------------

    public function set(string $key, mixed $value): static
    {
        $_SESSION[$key] = $value;
        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): static
    {
        unset($_SESSION[$key]);
        return $this;
    }

    public function all(): array
    {
        return $_SESSION;
    }

    // -------------------------------------------------------------------------
    //  Flash Messages (persist for ONE subsequent request)
    // -------------------------------------------------------------------------

    /**
     * Set a flash message.
     */
    public function flash(string $key, mixed $message): static
    {
        $_SESSION[self::FLASH_KEY]['new'][$key] = $message;
        return $this;
    }

    /**
     * Read a flash message from the current request.
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        return $_SESSION[self::FLASH_KEY]['current'][$key] ?? $default;
    }

    /**
     * Check if a flash message exists in the current request.
     */
    public function hasFlash(string $key): bool
    {
        return isset($_SESSION[self::FLASH_KEY]['current'][$key]);
    }

    // -------------------------------------------------------------------------
    //  Regenerate & Destroy
    // -------------------------------------------------------------------------

    /**
     * Regenerate the session ID (use after login to prevent session fixation).
     */
    public function regenerate(bool $deleteOld = true): static
    {
        session_regenerate_id($deleteOld);
        return $this;
    }

    /**
     * Destroy the session completely.
     */
    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies') && isset($_COOKIE[session_name()])) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        session_destroy();
    }

    // -------------------------------------------------------------------------
    //  Internal
    // -------------------------------------------------------------------------

    private function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Move flash "new" → "current" on every request.
     * "current" was the previous request's "new", so it's now accessible.
     */
    private function tickFlash(): void
    {
        $_SESSION[self::FLASH_KEY]['current'] = $_SESSION[self::FLASH_KEY]['new'] ?? [];
        $_SESSION[self::FLASH_KEY]['new'] = [];
    }

    public function __destruct()
    {
    }
}
