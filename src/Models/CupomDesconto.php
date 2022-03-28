<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class CupomDesconto extends Model
{
    protected $fillable = [
        'id',
        'nome_publico',
        'nome_interno',
        'codigo',
        'desconto',
        'numero_utilizacoes',
        'tipo_desconto_fornecedor',
        'total_vendido_venda',
        'total_vendido_net',
        'total_descontado_venda',
        'total_descontado_net',
        'status',
        'servico_id',
    ];




}
