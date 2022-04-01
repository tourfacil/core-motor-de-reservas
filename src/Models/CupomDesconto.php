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
        'maximo_utilizacoes',
        'tipo_desconto_fornecedor',
        'tipo_desconto_valor',
        'total_vendido_venda',
        'total_vendido_net',
        'total_descontado_venda',
        'total_descontado_net',
        'status',
        'servico_id',
    ];

    public function servico() {
        return $this->belongsTo(Servico::class);
    }




}
