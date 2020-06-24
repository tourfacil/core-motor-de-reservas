<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TransacaoPedido
 * @package TourFacil\Core\Models
 */
class TransacaoPedido extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        "pedido_id",
        "transacao"
    ];

    /**
     * @var array
     */
    protected $casts = [
        "transacao" => "object"
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}
