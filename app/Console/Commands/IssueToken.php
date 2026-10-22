<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueToken extends Command
{
    protected $signature = 'token:issue {plaza=test00} {--name=react-test}';
    protected $description = 'Emite un token de Sanctum para pruebas';

    public function handle()
    {
        $plain = bin2hex(random_bytes(20));
        $hash  = hash('sha256', $plain);

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id'   => 1,
            'name'           => $this->option('name'),
            'token'          => $hash,
            'abilities'      => json_encode(['*']),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $id = DB::table('personal_access_tokens')->max('id');
        $token = $id . '|' . $plain;

        $this->info('Token generado:');
        $this->line($token);
        $this->newLine();
        $this->comment('Úsalo en el navegador con:');
        $this->line("localStorage.setItem('jwt_token', '{$token}');");
        $this->line("localStorage.setItem('plaza', '{$this->argument('plaza')}');");
        $this->line("location.reload();");

        return 0;
    }
}