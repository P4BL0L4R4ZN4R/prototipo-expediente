<?php

namespace App\Console\Commands;

use App\Models\PersonalAccessToken;
use App\Services\TenantService;
use Illuminate\Console\Command;

class ServiceTokenCreate extends Command
{
    protected $signature = 'service-token:create {plaza : Plaza destino (test00, centro, norte, sur)}';
    protected $description = 'Genera un token de servicio para una plaza (reemplaza /_dev/token en producción)';

    public function handle(TenantService $tenant): int
    {
        $plaza = $this->argument('plaza');

        if (!$tenant->exists($plaza)) {
            $this->error("Plaza '{$plaza}' no existe en config/database.php");
            return self::FAILURE;
        }

        $tenant->set($plaza);

        $plain = bin2hex(random_bytes(20));
        $hash  = hash('sha256', $plain);

        $id = \DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id'   => 1,
            'name'           => 'service-' . now()->format('Ymd-His') . '-' . random_int(1000, 9999),
            'token'          => $hash,
            'abilities'      => json_encode(['*']),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $this->info("Token generado para plaza '{$plaza}':");
        $this->line('');
        $this->line("  <fg=green>{$id}|{$plain}</>");
        $this->line('');
        $this->line('Guárdalo, no se puede recuperar después.');

        return self::SUCCESS;
    }
}