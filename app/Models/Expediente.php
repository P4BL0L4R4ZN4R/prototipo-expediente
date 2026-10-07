<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
    protected $table      = 'expedientes';
    protected $primaryKey = 'id';
    public    $incrementing = false;
    protected $keyType    = 'string';

    protected $casts = [
        'ia'           => 'array',
        'data'         => 'array',
        'meta'         => 'array',
        'propuesta_ia' => 'array',
        'entregable'   => 'array',
    ];

    protected $fillable = ['id', 'ia', 'data', 'meta', 'propuesta_ia', 'entregable'];
}