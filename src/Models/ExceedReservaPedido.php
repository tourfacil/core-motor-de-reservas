<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use TourFacil\Core\Services\Integracao\NovaXS\Exceed\ExceedAPI;

/**
 * Class ExceedReservaPedido
 * @package TourFacil\Core\Models
 */
class ExceedReservaPedido extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'reserva_pedido_id',
        'bill_id',
        'data_servico',
        'voucher_impressao',
        'token_impressao',
        'status',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'data_servico'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reservaPedido()
    {
        return $this->belongsTo(ReservaPedido::class);
    }

    /**
     * URL do voucher
     *
     * @return string
     */
    public function getUrlVoucherAttribute()
    {
        return config('integracao.exceed.base_url') . "/voucher?voucher={$this->attributes['voucher_impressao']}&token={$this->attributes['token_impressao']}";
    }

    /**
     * PDF em bytes
     *
     * @return string
     */
    public function getVoucherAsByteAttribute()
    {
        return config('integracao.exceed.base_url') . "/voucher?voucher={$this->attributes['voucher_impressao']}&token={$this->attributes['token_impressao']}&method=receiptAsByte";
    }
}
