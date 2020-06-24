<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class QuantidadeReservaPedido
 * @package TourFacil\Core\Models
 */
class QuantidadeReservaPedido extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        "variacao_servico_id",
        "reserva_pedido_id",
        "quantidade",
        "valor_total",
        "valor_net",
    ];

    /**
     * @var array
     */
    protected $dates = [
        'deleted_at'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reservaPedido()
    {
        return $this->belongsTo(ReservaPedido::class);
    }

    /**
     * @return mixed
     */
    public function variacaoServico()
    {
        return $this->belongsTo(VariacaoServico::class)->withTrashed();
    }

    /**
     * Retorna se está ativo ou não
     *
     * @return bool
     */
    public function getStatusAttribute()
    {
        return ($this->attributes['deleted_at'] == null);
    }
}
