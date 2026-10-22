<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class TenantService
{
    private ?string $plaza = null;

    public function exists(string $plaza): bool
    {
        return config("database.connections.{$plaza}") !== null;
    }

    public function set(string $plaza): void
    {
        $config = config("database.connections.{$plaza}");
        if (!$config) {
            throw new \InvalidArgumentException("Conexión no configurada: {$plaza}");
        }

        config([
            'database.connections.tenant' => $config,
            'database.default' => 'tenant',
        ]);

        DB::purge('tenant');
        $this->plaza = $plaza;
    }

    public function get(): ?string { return $this->plaza; }
}