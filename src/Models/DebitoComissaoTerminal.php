<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use TourFacil\Core\Enum\ComissaoStatus;

/**
 * Class DebitoComissaoTerminal
 * @package TourFacil\Core\Models
 */
class DebitoComissaoTerminal extends Model
{
    /**
     * @var string
     */
    protected $table = "debito_comissao_terminais";

    /**
     * @var array
     */
    protected $fillable = [
        "terminal_id",
        "comissao_terminal_id",
        "valor",
        "status",
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
    public function comissaoTerminal()
    {
        return $this->belongsTo(ComissaoTerminal::class);
    }

    /**
     * @return mixed
     */
    public function getStatusDebitoAttribute()
    {
        return ComissaoStatus::STATUS[$this->attributes['status']];
    }

    /**
     * @return mixed
     */
    public function getCorStatusDebitoAttribute()
    {
        return ComissaoStatus::CORES_STATUS[$this->attributes['status']];
    }
}
