<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use TourFacil\Core\Models\ReservaPedido;

class Afiliado extends Model
{
    protected $fillable = [
        'nome_fantasia',
        'razao_social',
        'cpf',
        'cnpj',
        'email',
        'telefone',
        'site',
        'cep',
        'endereco',
        'bairro',
        'cidade',
        'estado',
        'comissao'
    ];

    protected function reservaPedidos() {
        return $this->hasMany(ReservaPedido::class);
    }
}
