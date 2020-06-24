<?php namespace TourFacil\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use TourFacil\Core\Enum\MotivosReservaEnum;

/**
 * Class HistoricoReservaPedido
 * @package TourFacil\Core\Models
 */
class HistoricoReservaPedido extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        "pedido_id",
        "reserva_pedido_id",
        "motivo",
        "user_id",
        "valor",
        "valor_fornecedor",
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return mixed
     */
    public function getMotivoHistoricoAttribute()
    {
        return MotivosReservaEnum::MOTIVOS[$this->attributes['motivo']];
    }
}
