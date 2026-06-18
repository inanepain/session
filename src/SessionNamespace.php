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
 * @author   Philip Michael Raab <philip@cathedral.co.za>
 * @package  inanepain\session
 * @category session
 *
 * @license  UNLICENSE
 * @license  https://unlicense.org/UNLICENSE UNLICENSE
 *
 * _version_ $version
 */

declare(strict_types = 1);

namespace Inane\Session;

use Inane\Stdlib\Exception\RuntimeException;

use function class_exists;
use function defined;

/**
 * SessionNamespace - Abstract base class for namespaced session access.
 *
 * This class provides a clean, type-safe way to work with session data
 * in a **dedicated namespace**, while inheriting all core functionality
 * from SessionManager.
 *
 * Features:
 * - Automatic namespace registration on first use
 * - Fluent static interface: `UserSession::set('id', 123)`
 * - Default namespace fallback via `getDefaultNamespace()`
 * - Full PHPDoc, IDE-friendly, PSR-compatible
 *
 * @version   1.0.0
 */
abstract class SessionNamespace {
    /**
     * The namespace used by this class.
     * Must be defined in child classes.
     *
     * @var string
     */
    protected const string NAMESPACE = 'default';

    /**
     * Optional fallback namespace if const is not set.
     * Override in child class if needed.
     *
     * @return string
     */
    protected static function getDefaultNamespace(): string {
        return 'default';
    }

    /**
     * Get the effective namespace for this class.
     *
     * @return string
     */
    protected static function namespace(): string {
        $ns = defined('static::NAMESPACE') ? static::NAMESPACE : '';

        return $ns !== '' ? $ns : static::getDefaultNamespace();
    }

    /**
     * Ensure session is initialised and namespace exists.
     *
     * @return void
     */
    private static function boot(): void {
        if (!class_exists(SessionManager::class)) {
            throw new RuntimeException('SessionManager must be included before using SessionNamespace.');
        }

        SessionManager::init();
        $ns = static::namespace();
        if ($ns !== SessionManager::currentNamespace()) {
            SessionManager::namespace($ns);
        }
    }

    #region Core session proxies (namespaced)

    /**
     * Set a value in this class's namespace.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public static function set(string $key, $value): void {
        self::boot();
        SessionManager::set($key, $value);
    }

    /**
     * Get a value from this class's namespace.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get(string $key, $default = null) {
        self::boot();

        return SessionManager::get($key, $default);
    }

    /**
     * Check if key exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function has(string $key): bool {
        self::boot();

        return SessionManager::has($key);
    }

    /**
     * Delete a key.
     *
     * @param string $key
     *
     * @return void
     */
    public static function delete(string $key): void {
        self::boot();
        SessionManager::delete($key);
    }

    /**
     * Get all data in this namespace.
     *
     * @return array<string,mixed>
     */
    public static function all(): array {
        self::boot();

        return SessionManager::all();
    }

    /**
     * Clear all data in this namespace.
     *
     * @return void
     */
    public static function clear(): void {
        self::boot();
        SessionManager::clear();
    }
    #endregion Core session proxies (namespaced)

    #region Flash Messages (namespaced)
    /**
     * Set flash message (one-request only).
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public static function flash(string $key, $value): void {
        self::boot();
        SessionManager::flash($key, $value);
    }

    /**
     * Get and consume flash message.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function getFlash(string $key, $default = null) {
        self::boot();

        return SessionManager::getFlash($key, $default);
    }

    /**
     * Check if flash exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function hasFlash(string $key): bool {
        self::boot();

        return SessionManager::hasFlash($key);
    }
    #endregion Flash Messages (namespaced)

    #region Utilities
    /**
     * Get current session ID.
     *
     * @return string
     */
    public static function id(): string {
        self::boot();

        return SessionManager::id();
    }

    /**
     * Regenerate session ID.
     *
     * @param bool $deleteOld
     *
     * @return void
     */
    public static function regenerate(bool $deleteOld = true): void {
        self::boot();
        SessionManager::regenerate($deleteOld);
    }

    /**
     * Destroy entire session (all namespaces).
     *
     * @return void
     */
    public static function destroy(): void {
        self::boot();
        SessionManager::destroy();
    }
    #endregion Utilities
}
