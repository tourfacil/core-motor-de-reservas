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

class AvaliacaoServico extends Model
{
    protected $fillable = [
        'servico_id',
        'nota',
        'avaliacao',
        'nome',
        'status',
        'reserva_pedido_id',
        'cliente_id',
    ];

    public function servico() {
        return $this->belongsTo(Servico::class);
    }
}
