<?php
// app/Core/SessionManager.php
namespace App\Core;

class SessionManager {
    public function start() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            session_start();
        }
    }

    public function regenerate() {
        if (!$this->has('user')) {
            return;
        }

        if (!$this->has('last_regen')) {
            $this->set('last_regen', time());
        } else if (time() - $this->get('last_regen') > 1800) {
            session_regenerate_id(true);
            $this->set('last_regen', time());
        }
    }

    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }

    public function has($key) {
        return isset($_SESSION[$key]);
    }

    public function remove($key) {
        unset($_SESSION[$key]);
    }

    public function destroy() {
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

    public function flash($key, $message) {
        $this->set('flash_' . $key, $message);
    }

    public function getFlash($key) {
        $message = $this->get('flash_' . $key);
        if ($message) {
            $this->remove('flash_' . $key);
        }
        return $message;
    }
}
