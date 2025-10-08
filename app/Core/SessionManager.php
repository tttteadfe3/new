<?php
// app/Core/SessionManager.php
namespace App\Core;

class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            session_start();
        }
    }

    public static function regenerate() {
        if (!self::has('last_regen')) {
            self::set('last_regen', time());
        } else if (time() - self::get('last_regen') > 1800) {
            session_regenerate_id(true);
            self::set('last_regen', time());
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public static function has($key) {
        return isset($_SESSION[$key]);
    }

    public static function remove($key) {
        unset($_SESSION[$key]);
    }

    public static function destroy() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function flash($key, $message) {
        self::set('flash_' . $key, $message);
    }



    public static function getFlash($key) {
        $message = self::get('flash_' . $key);
        if ($message) {
            self::remove('flash_' . $key);
        }
        return $message;
    }
}