<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Desconto extends Model
{
    protected $fillable = [
        'inicio',
        'final',
        'nome_publico',
        'nome_interno',
        'servico_id',
        'total_vendido_venda',
        'total_vendido_net',
        'total_descontado_venda',
        'total_descontado_net',
        'status',
        'tipo_desconto_valor',
        'tipo_desconto_fornecedor' ,
    ];
}
