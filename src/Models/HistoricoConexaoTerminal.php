<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use TourFacil\Core\Enum\TerminaisEnum;

/**
 * Class HistoricoConexaoTerminal
 * @package TourFacil\Core\Models
 */
class HistoricoConexaoTerminal extends Model
{
    /**
     * @var string
     */
    protected $table = "historico_conexao_terminais";

    /**
     * @var array
     */
    protected $fillable = [
        'terminal_id',
        'type',
        'payload'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'payload' => 'array'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function terminal()
    {
        return $this->belongsTo(Terminal::class);
    }

    /**
     * Tipo de historico
     *
     * @return mixed
     */
    public function getTipoHistoricoAttribute()
    {
        return TerminaisEnum::TIPOS_HISTORICO[$this->attributes['type']];
    }
}
