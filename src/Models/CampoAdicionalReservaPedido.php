<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CampoAdicionalReservaPedido
 * @package TourFacil\Core\Models
 */
class CampoAdicionalReservaPedido extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'campo_adicional_servico_id',
        'reserva_pedido_id',
        'informacao'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function campoAdicionalServico()
    {
        return $this->belongsTo(CampoAdicionalServico::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reservaPedido()
    {
        return $this->belongsTo(ReservaPedido::class);
    }

    /**
     * @param $informacao
     * @return string
     */
    public function setInformacaoAttribute($informacao)
    {
        return $this->attributes['informacao'] = ucfirst(mb_strtolower($informacao));
    }
}
