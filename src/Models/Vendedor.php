<?php
/*
 * Copyright (c) 2022. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class Vendedor extends Model
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
        'comissao',
        'comissao_passeios',
        'comissao_ingressos',
        'comissao_gastronomia',
        'comissao_transfer',
    ];

    protected function reservaPedidos() {
        return $this->hasMany(ReservaPedido::class);
    }
}
