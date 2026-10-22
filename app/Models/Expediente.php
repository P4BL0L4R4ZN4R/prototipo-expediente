<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
    protected $table      = 'expedientes';
    protected $primaryKey = 'id';
    public    $incrementing = true;
    protected $keyType    = 'string';

    protected $casts = [
        'ia'           => 'array',
        'data'         => 'array',
        'meta'         => 'array',
        'propuesta_ia' => 'array',
        'entregable'   => 'array',
        'fecha_propuesta'  => 'date',
        'fecha_entregable' => 'date',
        // 'estado_expediente' => 'string',
    ];

    protected $fillable = [
        'id',
        'ia',
        'data',
        'meta',
        'propuesta_ia',
        'entregable',
        'fecha_propuesta',
        'fecha_entregable',
        'estado_expediente',
        'direccion',
    ];
}