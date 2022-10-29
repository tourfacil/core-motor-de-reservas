<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class RegraServico extends Model
{
    protected $fillable = [
        'servico_id',
        'tipo_regra',
        'status',
        'regras',
        'prioridade',
    ];

    protected $casts = [
        'regras' => 'array',
    ];
}
