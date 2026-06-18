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

namespace Inane\Session\Tests;

use Inane\Session\SessionManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function headers_sent;

#[CoversClass(SessionManager::class)]
final class SessionManagerTest extends TestCase {
    protected function setUp(): void {
        // Ensure a fresh session per test
        if (!headers_sent()) {
            // If a PHP session is already active, destroy it via the manager
            try {
                // Destroy only if already initialised; ignore otherwise
                $ref = new \ReflectionClass(SessionManager::class);
                $prop = $ref->getProperty('initialised');
                $prop->setAccessible(true);
                if ($prop->getValue() === true) {
                    SessionManager::destroy();
                }
            } catch (\Throwable) {
                // ignore
            }
        }
        SessionManager::init();
    }

    protected function tearDown(): void {
        try {
            SessionManager::destroy();
        } catch (\Throwable) {
            // ignore
        }
    }

    public function testInitAndSetGetHasDelete(): void {
        $this->assertSame('default', SessionManager::currentNamespace());

        SessionManager::set('key', 'value');
        $this->assertTrue(SessionManager::has('key'));
        $this->assertSame('value', SessionManager::get('key'));
        $this->assertSame('fallback', SessionManager::get('missing', 'fallback'));

        SessionManager::delete('key');
        $this->assertFalse(SessionManager::has('key'));
    }

    public function testNamespaceIsolation(): void {
        SessionManager::namespace('alpha');
        SessionManager::set('k', 'A');

        SessionManager::namespace('beta');
        $this->assertFalse(SessionManager::has('k'));
        SessionManager::set('k', 'B');

        SessionManager::namespace('alpha');
        $this->assertSame('A', SessionManager::get('k'));

        SessionManager::namespace('beta');
        $this->assertSame('B', SessionManager::get('k'));
    }

    public function testFlashLifecycle(): void {
        // Set flash and read back
        SessionManager::flash('notice', 'saved');
        $this->assertTrue(SessionManager::hasFlash('notice'));
        $this->assertSame('saved', SessionManager::getFlash('notice'));

        // Flash should be gone after retrieval
        $this->assertFalse(SessionManager::hasFlash('notice'));
        $this->assertSame('x', SessionManager::getFlash('notice', 'x'));
    }

    public function testRememberMeEnableDisable(): void {
        $this->assertFalse(SessionManager::isRememberMe());

        SessionManager::enableRememberMe('1day');
        $this->assertTrue(SessionManager::isRememberMe());

        SessionManager::disableRememberMe();
        $this->assertFalse(SessionManager::isRememberMe());
    }

    public function testClearAndAll(): void {
        SessionManager::set('a', 1);
        SessionManager::set('b', 2);
        $all = SessionManager::all();
        $this->assertArrayHasKey('a', $all);
        $this->assertArrayHasKey('b', $all);

        SessionManager::clear();
        $this->assertSame([], SessionManager::all());
    }

    public function testRegenerateIdChanges(): void {
        $id1 = SessionManager::id();
        $this->assertNotSame('', $id1);

        // Force regenerate
        $method = (new \ReflectionClass(SessionManager::class))->getMethod('regenerate');
        $method->setAccessible(true);
        $method->invoke(null, true);

        $id2 = SessionManager::id();
        $this->assertNotSame($id1, $id2);
    }
}
