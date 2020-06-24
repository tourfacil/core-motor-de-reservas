<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use TourFacil\Core\Enum\ComissaoStatus;

/**
 * Class ComissaoTerminal
 * @package TourFacil\Core\Models
 */
class ComissaoTerminal extends Model
{
    /**
     * @var string
     */
    protected $table = 'comissao_terminais';

    /**
     * @var array
     */
    protected $fillable = [
        'pedido_id',
        'reserva_pedido_id',
        'terminal_id',
        'quantidade',
        'comissao',
        'data_previsao',
        'status',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'data_previsao'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function terminal()
    {
        return $this->belongsTo(Terminal::class);
    }

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function debitoComissao()
    {
        return $this->hasMany(DebitoComissaoTerminal::class)->latest();
    }

    /**
     * @return mixed
     */
    public function getStatusComissaoAttribute()
    {
        return ComissaoStatus::STATUS[$this->attributes['status']];
    }

    /**
     * @return mixed
     */
    public function getCorStatusComissaoAttribute()
    {
        return ComissaoStatus::CORES_STATUS[$this->attributes['status']];
    }
}
