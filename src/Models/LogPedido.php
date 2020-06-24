<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class LogPedido
 * @package TourFacil\Core\Models
 */
class LogPedido extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'codigo_pedido',
        'log',
        'cookie_ga',
        'tipo',
    ];
}
