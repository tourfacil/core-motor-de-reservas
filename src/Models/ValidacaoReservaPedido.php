<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class ValidacaoReservaPedido
 * @package TourFacil\Core\Models
 */
class ValidacaoReservaPedido extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        "pedido_id",
        "reserva_pedido_id",
        "validado",
        "observacoes",
    ];

    /**
     * @var array
     */
    protected $dates = [
        "validado"
    ];

    /**
     * @var array
     */
    protected $casts = [
        "observacoes" => "object"
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reservaPedido()
    {
        return $this->belongsTo(ReservaPedido::class);
    }
}
