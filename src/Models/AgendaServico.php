<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use TourFacil\Core\Enum\AgendaEnum;

/**
 * Class AgendaServico
 * @package TourFacil\Core\Models
 */
class AgendaServico extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'disponibilidade_minima',
        'compartilhada',
        'status',
        'dias_semana',
        'configuracoes'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'dias_semana' => 'array',
        'configuracoes' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servicos()
    {
        return $this->belongsToMany(Servico::class, 'agenda_has_servico')->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function datasServico()
    {
        return $this->hasMany(AgendaDataServico::class)->orderBy('data');
    }

    /**
     * @return array
     */
    public function getSubstituicoesAgendaAttribute()
    {
        $config = json_decode($this->attributes['configuracoes'], true);

        return $config[AgendaEnum::SUBSTITUICAO_AGENDA] ?? [];
    }

    /**
     * @return mixed
     */
    public function getStatusAgendaAttribute()
    {
        return AgendaEnum::STATUS_DISPONIBILIDADE[$this->attributes['status']];
    }
}
