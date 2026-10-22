<?php

namespace App\Http\Middleware;

use App\Models\PersonalAccessToken;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantAuth
{
    public function __construct(private TenantService $tenant) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plaza = $request->route('plaza');

        if (!$plaza || !$this->tenant->exists($plaza)) {
            abort(404, 'Plaza no encontrada');
        }

        $this->tenant->set($plaza);

        // Registrar la plaza en el contenedor para que servicios
        // como ExpedienteTextoStorage puedan aislar archivos por plaza.
        app()->instance('tenant.plaza', $plaza);

        // Validación manual del token con el modelo correcto
        $bearer = $request->bearerToken();
        if (!$bearer) {
            abort(401, 'Unauthenticated.');
        }

        // Tu modelo ya tiene $connection = 'tenant'
        $token = PersonalAccessToken::findToken($bearer);

        if (!$token) {
            abort(401, 'Unauthenticated.');
        }

        // Opcional: puedes autenticar al usuario manualmente si lo necesitas
        // auth()->setUser($token->tokenable);

        $request->route()->forgetParameter('plaza');

        return $next($request);
    }
}