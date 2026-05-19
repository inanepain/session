<?php

/**
 * Inane: Session
 *
 * A lightweight, secure and extensible PHP session handling library.
 *
 * $Id$
 * $Date$
 *
 * PHP version 8.5
 *
 * @author Philip Michael Raab <philip@cathedral.co.za>
 * @package inanepain\session
 * @category session
 *
 * @license UNLICENSE
 * @license https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types=1);

namespace Inane\Session;

use Inane\Datetime\Timespan;
use Inane\Stdlib\Exception\{
    InvalidArgumentException,
    RuntimeException};

use function array_merge;
use function ini_get;
use function ini_set;
use function is_int;
use function is_string;
use function session_destroy;
use function session_gc;
use function session_get_cookie_params;
use function session_id;
use function session_name;
use function session_regenerate_id;
use function session_start;
use function session_status;
use function setcookie;
use function time;

use const PHP_SESSION_NONE;

/**
 * SessionManager
 *
 * Features
 * --------
 * - Automatic session start with configurable, security-focused options
 * - Secure defaults (HTTP-only, Secure, SameSite cookies)
 * - Periodic session-ID regeneration to mitigate fixation attacks
 * - Flash messages (one-request lifespan)
 * - Namespaced storage to avoid key collisions
 * - Configurable inactivity timeout with automatic logout
 * - 'remember_me' support for persistent sessions (survives browser close)
 * - PSR-style static API, type-hinted, fully PHPDoc-ed
 *
 * @version   1.0.0
 */
class SessionManager {
    #region Properties
    /** @var bool Whether the manager has been initialised */
    private static bool $initialised = false;

    /** @var string Current namespace (default: 'default') */
    private static string $namespace = 'default';

    /** @var string Key used for flash data inside $_SESSION */
    private static string $flashKey = '__flash__';

    /** @var int Inactivity timeout in seconds (default 30 min) */
    private static int $timeout = 1800;

    /** @var int Session-ID regeneration interval in seconds (default 10 min) */
    private static int $regenerateInterval = 600;
    #endregion Properties

    #region Initialisation
    /**
     * Initialise the session with secure defaults.
     *
     * Must be called **once** before any other method. Subsequent calls are ignored.
     *
     * @param array<string,mixed> $options Override any default session configuration.
     *
     * @return void
     *
     * @throws RuntimeException If session cannot be started after recovery attempts.
     *
     * @phpstan-param array{
     *     cookie_lifetime?: int,
     *     cookie_path?: string,
     *     cookie_domain?: string,
     *     cookie_secure?: bool,
     *     cookie_httponly?: bool,
     *     cookie_samesite?: 'Lax'|'Strict'|'None',
     *     use_strict_mode?: bool,
     *     use_only_cookies?: bool,
     *     name?: string,
     *     gc_maxlifetime?: int,
     *     remember_me?: bool          // Enable persistent session (30 days lifetime)
     * } $options
     */
    public static function init(array $options = []): void {
        if (self::$initialised) return;

        $defaults = [
            'cookie_lifetime' => 0,                                 // 0 = expires on browser close
            'cookie_path'     => '/',
            'cookie_domain'   => '',
            'cookie_secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',                              // Lax | Strict | None
            'use_strict_mode' => true,
            'use_only_cookies' => true,
            'name'            => 'PHPSESSID',
            'gc_maxlifetime'  => 1440,
            'remember_me'     => false,                              // Persistent session (cookie_lifetime = 30 days)
        ];

        $config = array_merge($defaults, $options);

        // NEW: Handle 'remember_me' – set persistent lifetime if enabled
        if ($config['remember_me'] && $config['cookie_lifetime'] === 0) {
            $config['cookie_lifetime'] = Timespan::fromDuration('30days')->getSeconds();
        }

        // -----------------------------------------------------------------
        // Apply PHP ini settings (before any session ops)
        // -----------------------------------------------------------------
        session_name($config['name']);

        ini_set('session.use_only_cookies', $config['use_only_cookies'] ? '1' : '0');
        ini_set('session.use_strict_mode', $config['use_strict_mode'] ? '1' : '0');
        ini_set('session.cookie_lifetime', (string) $config['cookie_lifetime']);
        ini_set('session.cookie_path', $config['cookie_path']);
        ini_set('session.cookie_domain', $config['cookie_domain']);
        ini_set('session.cookie_secure', $config['cookie_secure'] ? '1' : '0');
        ini_set('session.cookie_httponly', $config['cookie_httponly'] ? '1' : '0');
        ini_set('session.cookie_samesite', $config['cookie_samesite']);
        ini_set('session.gc_maxlifetime', (string) $config['gc_maxlifetime']);

        // -----------------------------------------------------------------
        // Start the session
        // -----------------------------------------------------------------
        if (session_status() === PHP_SESSION_NONE && !session_start()) {
            throw new RuntimeException('Failed to start the session.');
        }

        session_gc();

        self::$initialised = true;

        // -----------------------------------------------------------------
        // Security housekeeping (now safe post-start)
        // -----------------------------------------------------------------
        self::regenerateIfNeeded();   // periodic ID regeneration
        self::checkTimeout();         // inactivity timeout
        self::clearFlash();           // remove stale flash data
    }
    #endregion Initialisation

    #region Remember me utilities
    /**
     * Enable "remember me" for the current session (persists after browser close).
     *
     * Sets cookie_lifetime to 30 days and updates session config.
     * Call after `init()`; regenerates ID for security.
     *
     * @param int|string|Timespan $lifetime Optional custom lifetime in seconds (default: 30 days).
     *
     * @return void
     */
    public static function enableRememberMe(int|string|Timespan $lifetime = '30days'): void {
        $lifetime = match(true) {
            is_int($lifetime) => $lifetime,
            is_string($lifetime) => Timespan::fromDuration($lifetime)->getSeconds(),
            default => $lifetime->getSeconds(),
        };

        self::ensureInitialised();
        ini_set('session.cookie_lifetime', (string) $lifetime);
        self::regenerate(true);  // Regenerate ID on enable for security
    }

    /**
     * Disable "remember me" (session expires on browser close).
     *
     * Resets cookie_lifetime to 0.
     *
     * @return void
     */
    public static function disableRememberMe(): void {
        self::ensureInitialised();
        ini_set('session.cookie_lifetime', '0');
    }

    /**
     * Check if current session is "remember me" (persistent).
     *
     * @return bool
     */
    public static function isRememberMe(): bool {
        self::ensureInitialised();
        return (int) ini_get('session.cookie_lifetime') > 0;
    }
    #endregion Remember me utilities

    #region Namespace handling
    /**
     * Switch the active namespace.
     *
     * All subsequent `set()`, `get()`, `has()`, `delete()` calls operate inside the
     * given namespace, preventing key collisions between modules.
     *
     * @param string $namespace Non-empty namespace identifier.
     *
     * @return void
     *
     * @throws InvalidArgumentException If namespace is empty.
     */
    public static function namespace(string $namespace): void {
        self::ensureInitialised();
        if ($namespace === '') {
            throw new InvalidArgumentException('Namespace cannot be empty.');
        }
        self::$namespace = $namespace;
        if (!isset($_SESSION[$namespace])) {
            $_SESSION[$namespace] = [];
        }
    }

    /**
     * Return the currently active namespace.
     *
     * @return string
     */
    public static function currentNamespace(): string {
        return self::$namespace;
    }
    #endregion Namespace handling

    #region Basic Get / Set
    /**
     * Store a value in the current namespace.
     *
     * @param string $key   Session key (non-empty).
     * @param mixed  $value Any serializable value.
     *
     * @return void
     *
     * @throws InvalidArgumentException If key is empty.
     */
    public static function set(string $key, $value): void {
        self::ensureInitialised();
        if ($key === '') {
            throw new InvalidArgumentException('Session key cannot be empty.');
        }
        $_SESSION[self::$namespace][$key] = $value;
        self::updateActivity();
    }

    /**
     * Retrieve a value from the current namespace.
     *
     * @param string $key     Session key.
     * @param mixed  $default Value returned when key does not exist.
     *
     * @return mixed
     */
    public static function get(string $key, $default = null) {
        self::ensureInitialised();
        return $_SESSION[self::$namespace][$key] ?? $default;
    }

    /**
     * Determine whether a key exists in the current namespace.
     *
     * @param string $key Session key.
     *
     * @return bool
     */
    public static function has(string $key): bool {
        self::ensureInitialised();
        return isset($_SESSION[self::$namespace][$key]);
    }

    /**
     * Remove a key from the current namespace.
     *
     * @param string $key Session key.
     *
     * @return void
     */
    public static function delete(string $key): void {
        self::ensureInitialised();
        unset($_SESSION[self::$namespace][$key]);
    }
    #endregion Basic Get / Set

    #region Flash Messages
    /**
     * Store a flash value – available only for the **next** request.
     *
     * @param string $key   Flash key.
     * @param mixed  $value Any serializable value.
     *
     * @return void
     */
    public static function flash(string $key, $value): void {
        self::ensureInitialised();
        if (!isset($_SESSION[self::$flashKey])) {
            $_SESSION[self::$flashKey] = [];
        }
        $_SESSION[self::$flashKey][$key] = $value;
    }

    /**
     * Retrieve and **erase** a flash value.
     *
     * @param string $key     Flash key.
     * @param mixed  $default Default if key missing.
     *
     * @return mixed
     */
    public static function getFlash(string $key, $default = null) {
        self::ensureInitialised();
        $value = $_SESSION[self::$flashKey][$key] ?? $default;
        unset($_SESSION[self::$flashKey][$key]);
        return $value;
    }

    /**
     * Check if a flash key exists (without consuming it).
     *
     * @param string $key Flash key.
     *
     * @return bool
     */
    public static function hasFlash(string $key): bool {
        self::ensureInitialised();
        return isset($_SESSION[self::$flashKey][$key]);
    }

    /**
     * Remove **all** stale flash data.
     *
     * Called automatically on every request after `init()`.
     *
     * @return void
     */
    private static function clearFlash(): void {
        if (isset($_SESSION[self::$flashKey])) {
            unset($_SESSION[self::$flashKey]);
        }
    }
    #endregion Flash Messages

    #region Session Regeneration
    /**
     * Force a new session ID.
     *
     * @param bool $deleteOld When true the old session file is removed.
     *
     * @return void
     */
    public static function regenerate(bool $deleteOld = true): void {
        self::ensureInitialised();
        session_regenerate_id($deleteOld);
        self::updateActivity();
    }

    /**
     * Regenerate the ID if the configured interval has elapsed.
     *
     * @return void
     */
    private static function regenerateIfNeeded(): void {
        $lastRegen = self::get('__last_regen__', 0);
        $now       = time();

        if ($now - $lastRegen > self::$regenerateInterval) {
            self::regenerate();
            self::set('__last_regen__', $now);
        }
    }

    /**
     * Configure the regeneration interval.
     *
     * Minimum 60 seconds; defaults to 600 s.
     *
     * @param int $seconds Interval in seconds.
     *
     * @return void
     */
    public static function setRegenerateInterval(int $seconds): void {
        self::$regenerateInterval = $seconds > 60 ? $seconds : 600;
    }
    #endregion Session Regeneration

    #region Timeout handling
    /**
     * Set inactivity timeout.
     *
     * @param int $seconds Timeout in seconds (minimum 1).
     *
     * @return void
     */
    public static function setTimeout(int $seconds): void {
        self::$timeout = $seconds > 0 ? $seconds : 1800;
    }

    /**
     * Check for inactivity and destroy the session if timed out.
     *
     * @return void
     */
    private static function checkTimeout(): void {
        $lastActivity = self::get('__last_activity__', time());
        if (time() - $lastActivity > self::$timeout) {
            self::destroy();
            return;
        }
        self::updateActivity();
    }

    /**
     * Refresh the internal activity timestamp.
     *
     * Directly writes to session to avoid recursion with `set()`.
     *
     * @return void
     */
    private static function updateActivity(): void {
        self::ensureInitialised();
        $_SESSION[self::$namespace]['__last_activity__'] = time();
    }
    #endregion Timeout handling

    #region Destruction / Utilities
    /**
     * Completely destroy the session and delete the cookie.
     *
     * @return void
     */
    public static function destroy(): void {
        self::ensureInitialised();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        self::$initialised = false;
    }

    /**
     * Return **all** data stored in the current namespace.
     *
     * @return array<string,mixed>
     */
    public static function all(): array {
        self::ensureInitialised();
        return $_SESSION[self::$namespace] ?? [];
    }

    /**
     * Remove every key inside the current namespace (keeps the session alive).
     *
     * @return void
     */
    public static function clear(): void {
        self::ensureInitialised();
        $_SESSION[self::$namespace] = [];
    }

    /**
     * Return the raw session identifier.
     *
     * @return string
     */
    public static function id(): string {
        self::ensureInitialised();
        return session_id();
    }
    #endregion Destruction / Utilities

    #region Internal Helpers
    /**
     * Throw if `init()` has not been called.
     *
     * @return void
     *
     * @throws RuntimeException
     */
    private static function ensureInitialised(): void {
        if (!self::$initialised) {
            throw new RuntimeException(
                'SessionManager must be initialised with SessionManager::init() before use.'
            );
        }
        if (!isset($_SESSION[self::$namespace])) {
            $_SESSION[self::$namespace] = [];
        }
    }
    #endregion Internal Helpers
}
