<?php

declare(strict_types=1);

namespace App\Http;

use App\Auth\AuthService;
use InvalidArgumentException;
use PDO;
use RuntimeException;

final class AuthController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function login(array $payload): array
    {
        $email = (string) ($payload['email'] ?? '');
        $service = new AuthService($this->pdo);

        return $service->login(
            $email,
            (string) ($payload['password'] ?? '')
        );
    }

    public function logout(string $token): array
    {
        $service = new AuthService($this->pdo);

        return $service->logout($token);
    }

    public static function statusCodeFor(RuntimeException | InvalidArgumentException $e, bool $isLogin): int
    {
        return $isLogin ? 401 : 422;
    }
}
