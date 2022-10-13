<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Traits\HasVoucher;
use TourFacil\Core\Models\Afiliado;

/**
 * Class ReservaPedido
 * @package TourFacil\Core\Models
 */
class ReservaPedido extends Model
{
    use HasVoucher, SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        "pedido_id",
        "servico_id",
        "fornecedor_id",
        "agenda_data_servico_id",
        "valor_total",
        "valor_net",
        'desconto_pix',
        "quantidade",
        "bloqueio_consumido",
        "status",
        "afiliado_id",
        "desconto_id",
        "finalizada",
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
    public function servico()
    {
        return $this->belongsTo(Servico::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agendaDataServico()
    {
        return $this->belongsTo(AgendaDataServico::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function quantidadeReserva()
    {
        return $this->hasMany(QuantidadeReservaPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dadoClienteReservaPedido()
    {
        return $this->hasMany(DadoClienteReservaPedido::class)
            ->withTrashed()->orderBy('deleted_at');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function campoAdicionalReservaPedido()
    {
        return $this->hasMany(CampoAdicionalReservaPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function comissaoTerminal()
    {
        return $this->hasOne(ComissaoTerminal::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function historicoReservaPedido()
    {
        return $this->hasMany(HistoricoReservaPedido::class)->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function validacao()
    {
        return $this->hasOne(ValidacaoReservaPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function snowlandVoucher()
    {
        return $this->hasOne(SnowlandReservaPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function exceedVoucher()
    {
        return $this->hasOne(ExceedReservaPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function olivasVoucher()
    {
        return $this->hasOne(OlivasReservaPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function miniMundoVoucher()
    {
        return $this->hasOne(MiniMundoReservaPedido::class);
    }

        /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dreamsVoucher()
    {
        return $this->hasOne(DreamsReservaPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function alpenVoucher()
    {
        return $this->hasOne(AlpenReservaPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function fantasticHouseVoucher()
    {
        return $this->hasOne(FantasticHouseReservaPedido::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function matriaVoucher()
    {
        return $this->hasOne(MatriaReservaPedido::class);
    }

    /**
     * @return mixed
     */
    public function getStatusReservaAttribute()
    {
        return StatusReservaEnum::STATUS[$this->attributes['status']];
    }

    /**
     * @return mixed
     */
    public function getCorStatusAttribute()
    {
        return StatusReservaEnum::CORES_STATUS[$this->attributes['status']];
    }

    public function afiliado() {
        return $this->belongsTo(Afiliado::class);
    }

    public function conferenciaReserva() {
        return $this->hasOne(ConferenciaReserva::class);
    }
}
