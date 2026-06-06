<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class AuthService
{
    public function login(array $payload): array
    {
        $email = trim(strtolower((string) ($payload['email'] ?? '')));
        $password = (string) ($payload['password'] ?? '');

        if ($email === '' || $password === '') {
            throw new InvalidArgumentException('email and password are required.');
        }

        $user = DB::table('users as u')
            ->leftJoin('user_roles as ur', 'ur.user_id', '=', 'u.id')
            ->leftJoin('roles as r', 'r.id', '=', 'ur.role_id')
            ->where('u.email', $email)
            ->where('u.active', 1)
            ->select(['u.id', 'u.name', 'u.email', 'u.password_hash', 'r.role_name'])
            ->first();

        if ($user === null || !password_verify($password, (string) ($user->password_hash ?? ''))) {
            throw new RuntimeException('Invalid credentials.');
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        DB::table('user_sessions')->insert([
            'user_id' => (int) $user->id,
            'token_hash' => $tokenHash,
            'expires_at' => now()->addHours(12)->format('Y-m-d H:i:s'),
        ]);

        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in_hours' => 12,
            'user' => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'role' => (string) ($user->role_name ?? 'Viewer'),
            ],
        ];
    }

    public function logout(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            throw new InvalidArgumentException('Bearer token is required.');
        }

        $tokenHash = hash('sha256', $token);
        $deleted = DB::table('user_sessions')->where('token_hash', $tokenHash)->delete();

        return [
            'revoked' => $deleted > 0,
        ];
    }
}
