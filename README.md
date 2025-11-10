# ![icon](./icon.png) inanepain/session

A lightweight, secure and extensible PHP session handling library.

# Introduction

SessionManager is a lightweight, secure, and extensible PHP session
handling library designed for modern web applications. It provides a
simple static API for managing sessions with built-in security features
like HTTP-only cookies, SameSite protection, periodic ID regeneration,
and inactivity timeouts. It supports namespacing to avoid key collisions
and "remember me" functionality for persistent sessions across browser
closes.

Key features: \* Automatic secure session initialization. \* Namespaced
storage to organize data (e.g., user, cart). \* Flash messages for
one-time notifications. \* Configurable timeouts and regeneration
intervals. \* Memory-safe handling for large sessions. \* Persistent
sessions via "remember me" (30-day default).

This library is dependency-free, PSR-compliant, and production-ready.

## Why SessionManager?

Native PHP sessions are powerful but lack secure defaults and
organization. SessionManager wraps `session_*` functions with best
practices, preventing common pitfalls like fixation attacks and key
conflicts.

# Install (Composer Recommended)

Add to your `composer.json`:

$ composer require inanepain/session

# Quick Start

## Basic Usage

Require the file and initialize:

    SessionManager::init([
        'name' => 'MYAPP_SESSID',
        // 'cookie_secure' => true,  // HTTPS only
        'cookie_samesite' => 'Strict',
    ]);

    // Set and get data
    SessionManager::set('user_id', 123);
    echo SessionManager::get('user_id');  // 123

    // Flash message
    SessionManager::flash('success', 'Login successful!');
    header('Location: /dashboard');
    exit;

    // In dashboard.php
    if (SessionManager::hasFlash('success')) {
        echo SessionManager::getFlash('success');
    }

    // Logout
    SessionManager::destroy();

## Namespaced Sessions

Use the base class `SessionNamespace` for modular access:

    class UserSession extends SessionNamespace
    {
        protected const NAMESPACE = 'user';
    }

    class CartSession extends SessionNamespace
    {
        protected const NAMESPACE = 'cart';
    }

    // Usage
    UserSession::set('id', 456);
    CartSession::set('items', ['item1']);
    echo UserSession::get('id');  // 456

# Configuration

Pass options to `init()` to customize behaviour. Defaults are secure.

<table>
<colgroup>
<col style="width: 25%" />
<col style="width: 25%" />
<col style="width: 25%" />
<col style="width: 25%" />
</colgroup>
<thead>
<tr>
<th style="text-align: left;">Option</th>
<th style="text-align: left;">Type</th>
<th style="text-align: left;">Default</th>
<th style="text-align: left;">Description</th>
</tr>
</thead>
<tbody>
<tr>
<td style="text-align: left;"><p>cookie_lifetime</p></td>
<td style="text-align: left;"><p>int</p></td>
<td style="text-align: left;"><p>0</p></td>
<td style="text-align: left;"><p>Cookie expiry (0 = browser close; &gt;0
for persistent).</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>cookie_path</p></td>
<td style="text-align: left;"><p>string</p></td>
<td style="text-align: left;"><p>/</p></td>
<td style="text-align: left;"><p>Cookie path.</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>cookie_domain</p></td>
<td style="text-align: left;"><p>string</p></td>
<td style="text-align: left;"><p>''</p></td>
<td style="text-align: left;"><p>Cookie domain.</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>cookie_secure</p></td>
<td style="text-align: left;"><p>bool</p></td>
<td style="text-align: left;"><p>HTTPS detected</p></td>
<td style="text-align: left;"><p>HTTPS-only cookie.</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>cookie_httponly</p></td>
<td style="text-align: left;"><p>bool</p></td>
<td style="text-align: left;"><p>true</p></td>
<td style="text-align: left;"><p>Prevent JS access.</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>cookie_samesite</p></td>
<td style="text-align: left;"><p>string</p></td>
<td style="text-align: left;"><p>Lax</p></td>
<td style="text-align: left;"><p>CSRF protection
(Lax/Strict/None).</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>use_strict_mode</p></td>
<td style="text-align: left;"><p>bool</p></td>
<td style="text-align: left;"><p>true</p></td>
<td style="text-align: left;"><p>Reject uninit sessions.</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>use_only_cookies</p></td>
<td style="text-align: left;"><p>bool</p></td>
<td style="text-align: left;"><p>true</p></td>
<td style="text-align: left;"><p>No URL param fallback.</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>name</p></td>
<td style="text-align: left;"><p>string</p></td>
<td style="text-align: left;"><p>PHPSESSID</p></td>
<td style="text-align: left;"><p>Session cookie name.</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>gc_maxlifetime</p></td>
<td style="text-align: left;"><p>int</p></td>
<td style="text-align: left;"><p>1440</p></td>
<td style="text-align: left;"><p>Garbage collection (minutes).</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>memory_limit</p></td>
<td style="text-align: left;"><p>string</p></td>
<td style="text-align: left;"><p>null</p></td>
<td style="text-align: left;"><p>Temp boost (e.g., '2G'; dev
only).</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>max_session_size</p></td>
<td style="text-align: left;"><p>int</p></td>
<td style="text-align: left;"><p>104857600</p></td>
<td style="text-align: left;"><p>Clear if &gt;100MB (bytes).</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>force_clear</p></td>
<td style="text-align: left;"><p>bool</p></td>
<td style="text-align: left;"><p>false</p></td>
<td style="text-align: left;"><p>Nuke session on init (dev).</p></td>
</tr>
<tr>
<td style="text-align: left;"><p>remember_me</p></td>
<td style="text-align: left;"><p>bool</p></td>
<td style="text-align: left;"><p>false</p></td>
<td style="text-align: left;"><p>Auto-set lifetime to 30 days.</p></td>
</tr>
</tbody>
</table>

Example with persistent session:

    SessionManager::init([
        'remember_me' => true,  // 30-day cookie
        // 'cookie_secure' => true,
    ]);

Runtime toggles:

    SessionManager::enableRememberMe(86400 * 7);  // 1 week
    SessionManager::disableRememberMe();  // Back to session-only
    echo SessionManager::isRememberMe() ? 'Persistent' : 'Temporary';

# API Reference

All methods are static. Call `init()` first.

## Initialization

<table>
<colgroup>
<col style="width: 40%" />
<col style="width: 60%" />
</colgroup>
<tbody>
<tr>
<td style="text-align: left;"><p>Method</p></td>
<td style="text-align: left;"><p>Description</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>init(array $options = []): void</code></p></td>
<td style="text-align: left;"><p>Start session with config.
Idempotent.</p></td>
</tr>
</tbody>
</table>

## Core Storage

<table>
<colgroup>
<col style="width: 40%" />
<col style="width: 60%" />
</colgroup>
<tbody>
<tr>
<td style="text-align: left;"><p>Method</p></td>
<td style="text-align: left;"><p>Description</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>set(string $key, mixed $value): void</code></p></td>
<td style="text-align: left;"><p>Store value; updates activity.</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>get(string $key, mixed $default = null): mixed</code></p></td>
<td style="text-align: left;"><p>Retrieve value.</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>has(string $key): bool</code></p></td>
<td style="text-align: left;"><p>Key exists?</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>delete(string $key): void</code></p></td>
<td style="text-align: left;"><p>Remove key.</p></td>
</tr>
<tr>
<td style="text-align: left;"><p><code>all(): array</code></p></td>
<td style="text-align: left;"><p>All namespace data.</p></td>
</tr>
<tr>
<td style="text-align: left;"><p><code>clear(): void</code></p></td>
<td style="text-align: left;"><p>Empty current namespace.</p></td>
</tr>
</tbody>
</table>

## Namespacing

<table>
<colgroup>
<col style="width: 40%" />
<col style="width: 60%" />
</colgroup>
<tbody>
<tr>
<td style="text-align: left;"><p>Method</p></td>
<td style="text-align: left;"><p>Description</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>namespace(string $namespace): void</code></p></td>
<td style="text-align: left;"><p>Switch namespace.</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>currentNamespace(): string</code></p></td>
<td style="text-align: left;"><p>Active namespace.</p></td>
</tr>
</tbody>
</table>

## Flash Messages

<table>
<colgroup>
<col style="width: 40%" />
<col style="width: 60%" />
</colgroup>
<tbody>
<tr>
<td style="text-align: left;"><p>Method</p></td>
<td style="text-align: left;"><p>Description</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>flash(string $key, mixed $value): void</code></p></td>
<td style="text-align: left;"><p>Set for next request.</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>getFlash(string $key, mixed $default = null): mixed</code></p></td>
<td style="text-align: left;"><p>Get and erase.</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>hasFlash(string $key): bool</code></p></td>
<td style="text-align: left;"><p>Exists? (non-destructive).</p></td>
</tr>
</tbody>
</table>

## Security & Lifecycle

<table>
<colgroup>
<col style="width: 40%" />
<col style="width: 60%" />
</colgroup>
<tbody>
<tr>
<td style="text-align: left;"><p>Method</p></td>
<td style="text-align: left;"><p>Description</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>regenerate(bool $deleteOld = true): void</code></p></td>
<td style="text-align: left;"><p>New ID; updates activity.</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>setRegenerateInterval(int $seconds): void</code></p></td>
<td style="text-align: left;"><p>Interval (min 60s).</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>setTimeout(int $seconds): void</code></p></td>
<td style="text-align: left;"><p>Inactivity timeout (min 1s).</p></td>
</tr>
<tr>
<td style="text-align: left;"><p><code>destroy(): void</code></p></td>
<td style="text-align: left;"><p>Full logout (clears
data/cookie).</p></td>
</tr>
<tr>
<td style="text-align: left;"><p><code>id(): string</code></p></td>
<td style="text-align: left;"><p>Raw session ID.</p></td>
</tr>
</tbody>
</table>

## Remember Me

<table>
<colgroup>
<col style="width: 40%" />
<col style="width: 60%" />
</colgroup>
<tbody>
<tr>
<td style="text-align: left;"><p>Method</p></td>
<td style="text-align: left;"><p>Description</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>enableRememberMe(int $lifetime = 2592000): void</code></p></td>
<td style="text-align: left;"><p>Enable persistent (regenerates
ID).</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>disableRememberMe(): void</code></p></td>
<td style="text-align: left;"><p>Disable (session-only).</p></td>
</tr>
<tr>
<td
style="text-align: left;"><p><code>isRememberMe(): bool</code></p></td>
<td style="text-align: left;"><p>Currently persistent?</p></td>
</tr>
</tbody>
</table>

# Examples

## Login with Remember Me

    SessionManager::init(['remember_me' => $_POST['remember'] ?? false]);

    if (authenticate($_POST['email'], $_POST['password'])) {
        SessionManager::set('user_id', $user->id);
        SessionManager::enableRememberMe();  // If checkbox checked
        SessionManager::flash('success', 'Welcome!');
        header('Location: /dashboard');
    } else {
        SessionManager::flash('error', 'Invalid credentials');
    }

## Namespaced E-Commerce

    UserSession::set('logged_in', true);
    CartSession::set('total', 99.99);
    echo CartSession::all();  // ['total' => 99.99]
    SessionManager::namespace('user');  // Switch back

# Security Considerations

- **Defaults**: HTTP-only, SameSite=Lax, strict mode enabled.

- **Regeneration**: Auto every 10min; manual via `regenerate()`.

- **Timeouts**: 30min inactivity → auto-destroy.

- **Persistent Sessions**: Use secure cookies; validate user on resume
  (e.g., DB token).

- **Large Data**: Avoid storing files/objects; use DB/Redis for &gt;1MB.

- **File Handler**: Auto-clears oversized/corrupt sessions; consider
  Redis for scale.
