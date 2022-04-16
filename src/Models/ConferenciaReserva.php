<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class ConferenciaReserva extends Model
{
    protected $fillable = [
        'reserva_pedido_id',
        'observacao',
        'status_conferencia_reserva',
    ];

    public function reservaPedido() {
        return $this->belongsTo(ReservaPedido::class);
    }
}
