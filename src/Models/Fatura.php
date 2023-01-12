<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Fatura extends Model
{
    protected $fillable = [
        'fornecedor_id',
        'inicio',
        'final',
        'data_pagamento',
        'status',
        'tipo', 
        'tipo_periodo', 
        'aprovacao_interna',
        'aprovacao_externa',
        'valor',
        'quantidade',
        'quantidade_reservas',
        'observacao'
    ];
}
