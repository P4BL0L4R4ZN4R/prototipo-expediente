<?php

require __DIR__ . '/vendor/autoload.php';

use Firebase\JWT\JWT;

$privateKey = file_get_contents(__DIR__ . '/storage/jwt/prueba_private.pem');
$audience   = 'tu-api-servicio'; // debe coincidir con EXTERNAL_JWT_AUDIENCE en .env
$now        = time();

$payload = [
    'iss' => 'sistema-externo-prueba',
    'sub' => 'consumidor-prueba',
    'aud' => $audience,
    'iat' => $now,
    'nbf' => $now,
    'exp' => $now + 3600,
    'jti' => bin2hex(random_bytes(16)),
];

$token = JWT::encode($payload, $privateKey, 'RS256');
echo $token . PHP_EOL;
