<?php

namespace App\Services\Storage;

use App\Models\Expediente;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExpedienteTextoStorage
{
    private const SECCIONES_PROPUESTA = [
        'texto_propuesta',
        'introduccion',
        'problematica',
        'objetivo_general',
        'objetivos_especificos',
        'metodologia',
    ];

    private const SECCIONES_ENTREGABLE = [
        'introduccion',
        'problematica',
        'desarrollo_conceptos',
        'finalizacion_servicio',
        'retrospectiva',
    ];

    // =========================================================
    // ESCRITURA
    // =========================================================

    public function guardarSeccion(string $id, string $tipo, string $seccion, string $texto): bool
    {
        if (!$this->seccionValida($tipo, $seccion)) {
            throw new \InvalidArgumentException("Sección inválida: {$tipo}.{$seccion}");
        }

        $exp = Expediente::find($id);
        if (!$exp) {
            Log::warning('[ExpedienteTextoStorage] Expediente no encontrado', ['id' => $id]);
            return false;
        }

        $path = $this->path($id, $tipo, $seccion);

        Storage::disk('expedientes')->put($path, $texto);

        $contenedor = $tipo === 'propuesta'
            ? ($exp->propuesta_ia ?? [])
            : ($exp->entregable ?? []);

        $pathActual = $contenedor[$seccion]['path'] ?? null;

        if ($pathActual !== $path) {
            $contenedor[$seccion] = ['path' => $path];

            if ($tipo === 'propuesta') $exp->propuesta_ia = $contenedor;
            else                        $exp->entregable = $contenedor;

            $exp->save();
        }

        return true;
    }

    // =========================================================
    // LECTURA
    // =========================================================

    public function leerSeccion(string $id, string $tipo, string $seccion): string
    {
        $exp = Expediente::find($id);
        if (!$exp) return '';

        $contenedor = $tipo === 'propuesta'
            ? ($exp->propuesta_ia ?? [])
            : ($exp->entregable ?? []);

        $meta = $contenedor[$seccion] ?? null;

        // Nuevo esquema
        if (isset($meta['path'])) {
            return $this->leerArchivo($meta['path']);
        }

        // Esquema viejo
        if (isset($meta['texto'])) {
            return $meta['texto'];
        }

        return '';
    }

    public function obtenerPropuestaCompleta(string $id): array
    {
        $resultado = [];
        foreach (self::SECCIONES_PROPUESTA as $seccion) {
            $resultado[$seccion] = [
                'ok'    => $this->existeSeccion($id, 'propuesta', $seccion),
                'texto' => $this->leerSeccion($id, 'propuesta', $seccion),
            ];
        }
        return $resultado;
    }

    public function obtenerEntregableCompleto(string $id): array
    {
        $resultado = [];
        foreach (self::SECCIONES_ENTREGABLE as $seccion) {
            $resultado[$seccion] = [
                'ok'    => $this->existeSeccion($id, 'entregable', $seccion),
                'texto' => $this->leerSeccion($id, 'entregable', $seccion),
            ];
        }
        return $resultado;
    }

    public function obtenerSeccion(string $id, string $tipo, string $seccion): array
    {
        return [
            'ok'    => $this->existeSeccion($id, $tipo, $seccion),
            'texto' => $this->leerSeccion($id, $tipo, $seccion),
        ];
    }

    public function existeSeccion(string $id, string $tipo, string $seccion): bool
    {
        $exp = Expediente::find($id);
        if (!$exp) return false;

        $contenedor = $tipo === 'propuesta'
            ? ($exp->propuesta_ia ?? [])
            : ($exp->entregable ?? []);

        $meta = $contenedor[$seccion] ?? null;

        if (isset($meta['path'])) {
            return Storage::disk('expedientes')->exists($meta['path']);
        }

        if (isset($meta['texto'])) {
            return trim($meta['texto']) !== '';
        }

        return false;
    }

    // =========================================================
    // ELIMINACIÓN
    // =========================================================

    public function eliminarSeccion(string $id, string $tipo, string $seccion): bool
    {
        $exp = Expediente::find($id);
        if (!$exp) {
            Log::warning('[ExpedienteTextoStorage::eliminarSeccion] Expediente no encontrado', [
                'id' => $id,
            ]);
            return false;
        }

        $contenedor = $tipo === 'propuesta'
            ? ($exp->propuesta_ia ?? [])
            : ($exp->entregable ?? []);

        $meta = $contenedor[$seccion] ?? null;

        // ─── Borrar el archivo físico ───────────────────────────
        $path = $meta['path'] ?? null;

        if ($path) {
            $existe = Storage::disk('expedientes')->exists($path);
            $rutaReal = Storage::disk('expedientes')->path($path);

            if ($existe) {
                Storage::disk('expedientes')->delete($path);
            } else {
                Log::warning('[ExpedienteTextoStorage::eliminarSeccion] Archivo no encontrado en disco', [
                    'path'      => $path,
                    'ruta_real' => $rutaReal,
                ]);
            }
        } else {
            Log::warning('[ExpedienteTextoStorage::eliminarSeccion] No hay path en BD', [
                'seccion' => $seccion,
            ]);
        }

        // ─── Quitar de la BD ────────────────────────────────────
        unset($contenedor[$seccion]);

        if ($tipo === 'propuesta') $exp->propuesta_ia = $contenedor;
        else                        $exp->entregable = $contenedor;

        $exp->save();

        return true;
    }

    public function eliminarExpediente(string $id): bool
    {
        $plaza = $this->plazaActual();
        $dir   = "{$plaza}/{$id}";

        if (Storage::disk('expedientes')->exists($dir)) {
            Storage::disk('expedientes')->deleteDirectory($dir);
        }
        return true;
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Construye el path con la plaza como prefijo para evitar
     * colisiones entre plazas que comparten el mismo filesystem.
     */
    private function path(string $id, string $tipo, string $seccion): string
    {
        $plaza = $this->plazaActual();
        return "{$plaza}/{$id}/{$tipo}/{$seccion}.txt";
    }

    /**
     * Devuelve la plaza activa desde el contenedor de Laravel.
     * TenantAuth la registra al resolver el middleware.
     */
    private function plazaActual(): string
    {
        return app()->bound('tenant.plaza')
            ? app('tenant.plaza')
            : 'default';
    }

    private function leerArchivo(?string $path): string
    {
        if (!$path) return '';

        if (!Storage::disk('expedientes')->exists($path)) {
            Log::warning('[ExpedienteTextoStorage] Archivo faltante', ['path' => $path]);
            return '';
        }

        return Storage::disk('expedientes')->get($path);
    }

    private function seccionValida(string $tipo, string $seccion): bool
    {
        $permitidas = match ($tipo) {
            'propuesta'  => self::SECCIONES_PROPUESTA,
            'entregable' => self::SECCIONES_ENTREGABLE,
            default      => [],
        };
        return in_array($seccion, $permitidas, true);
    }
}