<?php

class UserController extends Controller
{
    public function index(): void
    {
        $this->requireRole('admin');

        $clientId = $GLOBALS['client_id'];
        $service = new UserManagementService();
        $approvalService = new ApprovalService();
        $emailService = new EmailService();

        $users = $service->listUsers($clientId);
        $approvalSettings = $approvalService->getSettings($clientId);
        $smtpConfigured = $emailService->isConfigured();

        $deletedUsers = $service->listDeletedUsers($clientId);

        $this->view('users/index', [
            'pageTitle' => 'User Management',
            'users' => $users,
            'deletedUsers' => $deletedUsers,
            'approvalSettings' => $approvalSettings,
            'smtpConfigured' => $smtpConfigured,
        ]);
    }

    public function create(): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $email = trim($input['email'] ?? '');
        $firstName = trim($input['first_name'] ?? '');
        $role = $input['role'] ?? 'editor';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            @ob_clean();
            $this->json(['error' => 'Valid email address is required.'], 400);
            return;
        }

        if (!in_array($role, ['admin', 'editor', 'reviewer'], true)) {
            $role = 'editor';
        }

        $clientId = $GLOBALS['client_id'];
        $service = new UserManagementService();
        $result = $service->createUser($clientId, $email, $firstName, $role);

        if (!$result['success']) {
            @ob_clean();
            $this->json(['error' => $result['error']], 400);
            return;
        }

        (new ActivityLogService())->logUserAction('user_created', (int)$result['user_id'], $firstName ?: $email, [
            'email' => $email,
            'role'  => $role,
        ]);

        // Try to send invitation email
        $inviteResult = $service->inviteUser($result['user_id'], $clientId, $result['temp_password']);

        @ob_clean();
        $this->json([
            'success' => true,
            'user_id' => $result['user_id'],
            'username' => $result['username'],
            'email_sent' => $inviteResult['success'] ?? false,
            'email_error' => $inviteResult['error'] ?? null,
            'needs_smtp' => $inviteResult['needs_smtp'] ?? false,
            // Only show temp password if email wasn't sent (so admin can share it manually)
            'temp_password' => ($inviteResult['success'] ?? false) ? null : $result['temp_password'],
        ]);
    }

    public function update(string $id): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        // Snapshot old state so we can detect role changes after update
        $before = Database::fetch(
            "SELECT id, first_name, username, email, role FROM users WHERE id = :id AND client_id = :cid",
            ['id' => (int)$id, 'cid' => $GLOBALS['client_id']]
        );

        $service = new UserManagementService();
        $updated = $service->updateUser((int)$id, $GLOBALS['client_id'], $input);

        if ($updated && $before) {
            $targetName = $before['first_name'] ?: ($before['username'] ?: $before['email'] ?: ('user #' . $id));
            $logService = new ActivityLogService();

            // Detect role change specifically (it's the most important signal)
            if (isset($input['role']) && $input['role'] !== $before['role']) {
                $logService->logUserAction('role_changed', (int)$id, $targetName, [
                    'old_role' => $before['role'],
                    'new_role' => $input['role'],
                ]);
            }

            // If a new password was set, log that too
            if (!empty($input['new_password'])) {
                $logService->logUserAction('password_reset_by_admin', (int)$id, $targetName);
            }

            // General update event (only if something other than role/password changed)
            $changedKeys = [];
            foreach (['first_name', 'email', 'is_active'] as $k) {
                if (array_key_exists($k, $input) && (string)($before[$k] ?? '') !== (string)$input[$k]) {
                    $changedKeys[] = $k;
                }
            }
            if (!empty($changedKeys)) {
                $logService->logUserAction('user_updated', (int)$id, $targetName, [
                    'fields_changed' => $changedKeys,
                ]);
            }
        }

        @ob_clean();
        $this->json(['success' => $updated]);
    }

    public function deactivate(string $id): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $target = Database::fetch("SELECT first_name, username, email FROM users WHERE id = :id AND client_id = :cid", ['id' => (int)$id, 'cid' => $GLOBALS['client_id']]);
        $service = new UserManagementService();
        $result = $service->deactivateUser((int)$id, $GLOBALS['client_id']);

        if ($result && $target) {
            $name = $target['first_name'] ?: ($target['username'] ?: $target['email']);
            (new ActivityLogService())->logUserAction('user_deactivated', (int)$id, $name);
        }

        @ob_clean();
        $this->json(['success' => $result]);
    }

    public function activate(string $id): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $target = Database::fetch("SELECT first_name, username, email FROM users WHERE id = :id AND client_id = :cid", ['id' => (int)$id, 'cid' => $GLOBALS['client_id']]);
        $service = new UserManagementService();
        $result = $service->activateUser((int)$id, $GLOBALS['client_id']);

        if ($result && $target) {
            $name = $target['first_name'] ?: ($target['username'] ?: $target['email']);
            (new ActivityLogService())->logUserAction('user_activated', (int)$id, $name);
        }

        @ob_clean();
        $this->json(['success' => $result]);
    }

    public function deleteUser(string $id): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $target = Database::fetch("SELECT first_name, username, email FROM users WHERE id = :id AND client_id = :cid", ['id' => (int)$id, 'cid' => $GLOBALS['client_id']]);
        $service = new UserManagementService();
        $result = $service->softDeleteUser((int)$id, $GLOBALS['client_id']);

        if ($result && $target) {
            $name = $target['first_name'] ?: ($target['username'] ?: $target['email']);
            (new ActivityLogService())->logUserAction('user_deleted', (int)$id, $name);
        }

        @ob_clean();
        $this->json(['success' => $result]);
    }

    public function restoreUser(string $id): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $target = Database::fetch("SELECT first_name, username, email FROM users WHERE id = :id AND client_id = :cid", ['id' => (int)$id, 'cid' => $GLOBALS['client_id']]);
        $service = new UserManagementService();
        $result = $service->restoreUser((int)$id, $GLOBALS['client_id']);

        if ($result && $target) {
            $name = $target['first_name'] ?: ($target['username'] ?: $target['email']);
            (new ActivityLogService())->logUserAction('user_restored', (int)$id, $name);
        }

        @ob_clean();
        $this->json(['success' => $result]);
    }

    public function permanentDelete(string $id): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $userId = (int)$id;
        $clientId = $GLOBALS['client_id'];

        // Can't delete yourself
        if ($userId === (int)($_SESSION['user_id'] ?? 0)) {
            @ob_clean();
            $this->json(['error' => 'You cannot delete your own account.'], 400);
            return;
        }

        // Verify user exists and belongs to this client
        $user = Database::fetch("SELECT id, first_name, username, email FROM users WHERE id = :id AND client_id = :cid", ['id' => $userId, 'cid' => $clientId]);
        if (!$user) {
            @ob_clean();
            $this->json(['error' => 'User not found.'], 404);
            return;
        }

        // Permanently delete
        $db = Database::connect();
        $db->prepare("DELETE FROM users WHERE id = ? AND client_id = ?")->execute([$userId, $clientId]);

        $name = $user['first_name'] ?: ($user['username'] ?: ($user['email'] ?? ''));
        (new ActivityLogService())->logUserAction('user_permanently_deleted', $userId, $name);

        @ob_clean();
        $this->json(['success' => true]);
    }

    public function resendInvite(string $id): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $service = new UserManagementService();
        $clientId = $GLOBALS['client_id'];

        $target = Database::fetch("SELECT first_name, username, email FROM users WHERE id = :id AND client_id = :cid", ['id' => (int)$id, 'cid' => $clientId]);
        $tempPassword = $service->resetPassword((int)$id, $clientId);
        if (!$tempPassword) {
            @ob_clean();
            $this->json(['error' => 'User not found.'], 404);
            return;
        }

        $name = $target ? ($target['first_name'] ?: ($target['username'] ?: $target['email'])) : 'user #' . $id;
        $logService = new ActivityLogService();
        $logService->logUserAction('password_reset_by_admin', (int)$id, $name);

        $inviteResult = $service->inviteUser((int)$id, $clientId, $tempPassword);
        if (!empty($inviteResult['success'])) {
            $logService->logUserAction('invite_sent', (int)$id, $name);
        }

        @ob_clean();
        $this->json([
            'success' => true,
            'email_sent' => $inviteResult['success'] ?? false,
            'email_error' => $inviteResult['error'] ?? null,
            'temp_password' => ($inviteResult['success'] ?? false) ? null : $tempPassword,
        ]);
    }

    public function saveApprovalSettings(): void
    {
        $this->requireRole('admin');

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || ($input['csrf_token'] ?? '') !== ($_SESSION['csrf_token'] ?? '')) {
            $this->json(['error' => 'Invalid request.'], 403);
            return;
        }

        $approvalService = new ApprovalService();
        $approvalService->saveSettings($GLOBALS['client_id'], $input);

        @ob_clean();
        $this->json(['success' => true]);
    }
}
