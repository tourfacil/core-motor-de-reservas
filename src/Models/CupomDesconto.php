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
        'status',
        'servico_id',
    ];

    public function servico() {
        return $this->belongsTo(Servico::class);
    }




}
