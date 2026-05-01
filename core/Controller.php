<?php

class Controller
{
    protected function view(string $viewName, array $data = []): void
    {
        extract($data);
        $viewFile = APP_ROOT . '/app/views/' . $viewName . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: {$viewName}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        require APP_ROOT . '/app/views/layouts/main.php';
    }

    protected function viewOnly(string $viewName, array $data = []): void
    {
        extract($data);
        $viewFile = APP_ROOT . '/app/views/' . $viewName . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View not found: {$viewName}");
        }

        require $viewFile;
    }

    protected function json(array $data, int $status = 200): void
    {
        // Clean any buffered output (PHP warnings, etc.) to keep JSON clean
        while (ob_get_level()) {
            ob_end_clean();
        }
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . $path);
        exit;
    }

    protected function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        // Check if user account is still active
        if (isset($_SESSION['user_id']) && isset($_SESSION['is_active']) && !$_SESSION['is_active']) {
            session_destroy();
            $this->redirect('/login');
        }
    }

    protected function requireRole(string ...$roles): void
    {
        $this->requireAuth();
        $userRole = $_SESSION['role'] ?? '';
        if (!in_array($userRole, $roles, true)) {
            http_response_code(403);
            echo '<h1>403 — Access Denied</h1><p>You do not have permission to view this page.</p>';
            echo '<p><a href="' . BASE_URL . '/dashboard">Back to Dashboard</a></p>';
            exit;
        }
    }

    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function verifyCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? '';
        if ($token === '') {
            $raw = file_get_contents('php://input');
            if ($raw !== '' && $raw !== false) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded['csrf_token'])) {
                    $token = (string) $decoded['csrf_token'];
                }
            }
        }
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
}
