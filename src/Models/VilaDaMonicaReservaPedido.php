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

class VilaDaMonicaReservaPedido extends Model
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
        return config('integracao.vila_da_monica.base_url') . "/voucher?voucher={$this->attributes['voucher_impressao']}&token={$this->attributes['token_impressao']}";
    }

    /**
     * PDF em bytes
     *
     * @return string
     */
    public function getVoucherAsByteAttribute()
    {
        return config('integracao.vila_da_monica.base_url') . "/voucher?voucher={$this->attributes['voucher_impressao']}&token={$this->attributes['token_impressao']}&method=receiptAsByte";
    }
}
