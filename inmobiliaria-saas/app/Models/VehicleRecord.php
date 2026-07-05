<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleRecord extends Model
{
    public const CONCEPTS_BY_CATEGORY = [
        'ingreso' => ['FLETE'],
        'gasto' => ['MANTENIMIENTO', 'COMBUSTIBLE', 'UREA', 'VIATICOS', 'PEAJES', 'PARQUEADERO', 'SALARIO'],
    ];

    protected $fillable = [
        'record_date',
        'category',
        'concept',
        'description',
        'amount',
        'status',
    ];

    protected $casts = [
        'record_date' => 'date',
        'amount' => 'decimal:2',
    ];
}
