<?php

declare(strict_types=1);

namespace App\Auth;

use InvalidArgumentException;
use PDO;
use RuntimeException;

final class AuthService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function login(string $email, string $password): array
    {
        $email = trim(strtolower($email));
        if ($email === '' || $password === '') {
            throw new InvalidArgumentException('email and password are required.');
        }

        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.name, u.email, u.password_hash, r.role_name
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.email = :email AND u.active = 1
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            throw new RuntimeException('Invalid credentials.');
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $insert = $this->pdo->prepare(
            'INSERT INTO user_sessions (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 12 HOUR))'
        );
        $insert->execute([
            ':user_id' => (int) $user['id'],
            ':token_hash' => $tokenHash,
        ]);

        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in_hours' => 12,
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role_name'] ?? 'Viewer',
            ],
        ];
    }

    public function authenticateToken(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            throw new InvalidArgumentException('Bearer token is required.');
        }

        $tokenHash = hash('sha256', $token);

        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.name, u.email, r.role_name
             FROM user_sessions s
             INNER JOIN users u ON u.id = s.user_id
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE s.token_hash = :token_hash
               AND s.expires_at > CURRENT_TIMESTAMP
               AND u.active = 1
             LIMIT 1'
        );
        $stmt->execute([':token_hash' => $tokenHash]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new RuntimeException('Invalid or expired token.');
        }

        return [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) ($user['role_name'] ?? 'Viewer'),
        ];
    }

    public function authorizeRole(array $user, array $allowedRoles): void
    {
        $role = (string) ($user['role'] ?? 'Viewer');
        if (!in_array($role, $allowedRoles, true)) {
            throw new RuntimeException('You do not have permission for this action.');
        }
    }

    public function logout(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            throw new InvalidArgumentException('Bearer token is required.');
        }

        $tokenHash = hash('sha256', $token);

        $stmt = $this->pdo->prepare('DELETE FROM user_sessions WHERE token_hash = :token_hash');
        $stmt->execute([':token_hash' => $tokenHash]);

        return [
            'revoked' => $stmt->rowCount() > 0,
        ];
    }
}
