<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class IntegracaoPWI extends Model
{
    protected $table = 'integracao_p_w_is';

    protected $fillable = [
        'reserva_pedido_id',
        'integracao',
        'status',
        'dados',
        'data_utilizacao',
    ];

    protected $casts = [
        'dados' => 'array',
    ];
}
