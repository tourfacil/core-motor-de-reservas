<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class EnderecoCliente extends Model
{
    protected $fillable = [
        'cliente_id',
        'rua',
        'numero',
        'bairro',
        'cidade',
        'estado',
        'cep'
    ];

    public function cliente() {
        return $this->belongsTo(Cliente::class);
    }
}
