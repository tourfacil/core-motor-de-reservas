<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Enum\VariacaoServicoEnum;

/**
 * Class VariacaoServico
 * @package TourFacil\Core\Models
 */
class VariacaoServico extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'servico_id',
        'nome',
        'descricao',
        'percentual',
        'markup',
        'destaque',
        'consome_bloqueio',
        'min_pax',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    /**
     * @return mixed
     */
    public function getBloqueioAttribute()
    {
        return VariacaoServicoEnum::STATUS_BLOQUEIO[$this->attributes['consome_bloqueio']];
    }

    /**
     * Retorna se está ativo ou não
     *
     * @return bool
     */
    public function getStatusAttribute()
    {
        return ($this->attributes['deleted_at'] == null);
    }

    /**
     * @return bool
     */
    public function getVariacaoDestaqueAttribute()
    {
        return ($this->attributes['destaque'] == VariacaoServicoEnum::VARIACAO_DESTAQUE);
    }
}
