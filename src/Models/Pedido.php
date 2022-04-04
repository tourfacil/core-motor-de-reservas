<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Enum\MetodoPagamentoEnum;
use TourFacil\Core\Enum\OrigemEnum;
use TourFacil\Core\Enum\StatusPagamentoEnum;
use TourFacil\Core\Enum\StatusPedidoEnum;

/**
 * Class Pedido
 * @package TourFacil\Core\Models
 */
class Pedido extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'cliente_id',
        'codigo',
        'canal_venda_id',
        'valor_total',
        'juros',
        'origem',
        'status',
        'status_pagamento',
        'metodo_pagamento',
        'cupom_desconto_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cliente()
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function canalVenda()
    {
        return $this->belongsTo(CanalVenda::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function transacaoPedido()
    {
        return $this->hasOne(TransacaoPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reservas()
    {
        return $this->hasMany(ReservaPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comissaoTerminal()
    {
        return $this->hasMany(ComissaoTerminal::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function historicoPedido()
    {
        return $this->hasMany(HistoricoReservaPedido::class)->latest();
    }

    /**
     * Forma de pagamento do pedido
     *
     * @return mixed
     */
    public function getFormaPagamentoAttribute()
    {
        return MetodoPagamentoEnum::METHODS[$this->attributes['metodo_pagamento']];
    }

    /**
     * @return mixed
     */
    public function getStatusPedidoAttribute()
    {
        return StatusPedidoEnum::STATUS[$this->attributes['status']];
    }

    /**
     * @return mixed
     */
    public function getCorStatusPedidoAttribute()
    {
        return StatusPedidoEnum::COR_STATUS[$this->attributes['status']];
    }

    /**
     * @return mixed
     */
    public function getPagamentoStatusAttribute()
    {
        return StatusPagamentoEnum::STATUS[$this->attributes['status_pagamento']];
    }

    /**
     * @return mixed
     */
    public function getCorStatusPagamentoAttribute()
    {
        return StatusPagamentoEnum::CORES_STATUS[$this->attributes['status_pagamento']];
    }

    /**
     * @return mixed
     */
    public function getOrigemPedidoAttribute()
    {
        return OrigemEnum::ORIGENS[$this->attributes['origem']];
    }
}
