<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class AgendaDataServico
 * @package TourFacil\Core\Models
 */
class AgendaDataServico extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'agenda_servico_id',
        'data',
        'valor_net',
        'valor_venda',
        'disponivel',
        'consumido',
        'status',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'data'
    ];

    /**
     * @return string
     */
    public function getValorNetBrAttribute()
    {
        return formataValor($this->attributes['valor_net']);
    }

    /**
     * @return string
     */
    public function getValorVendaBrAttribute()
    {
        return formataValor($this->attributes['valor_venda']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function agendaServico()
    {
        return $this->belongsTo(AgendaServico::class);
    }
}
