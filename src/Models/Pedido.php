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
        'cliente_email',
        'codigo',
        'canal_venda_id',
        'valor_total',
        'juros',
        'desconto_pix',
        'origem',
        'status',
        'status_pagamento',
        'metodo_pagamento',
        'meio_pagamento_interno',
        'metodo_pagamento_interno',
        'cupom_desconto_id',
        'email_avaliacao',
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
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pedidoAvaliacaoMailHashLogin()
    {
        return $this->hasOne(PedidoAvaliacaoMailHashLogin::class);
    }

    public function cupom()
    {
        return $this->belongsTo(CupomDesconto::class, 'cupom_desconto_id');
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
