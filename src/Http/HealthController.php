<?php

declare(strict_types=1);

namespace App\Http;

final class HealthController
{
    public function show(): array
    {
        return [
            'status' => 'ok',
            'service' => 'resource-planner-ai',
        ];
    }
}
