<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Desconto extends Model
{
    protected $fillable = [
        'inicio',
        'final',
        'inicio_utilizacao',
        'final_utilizacao',
        'nome_publico',
        'nome_interno',
        'desconto',
        'servico_id',
        'status',
        'tipo_desconto_valor',
        'tipo_desconto_fornecedor' ,
    ];

    protected $dates = [
        'inicio',
        'final',
        'inicio_utilizacao',
        'final_utilizacao'
    ];
}
