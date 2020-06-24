<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Enum\CampoAdicionalEnum;

/**
 * Class CampoAdicionalServico
 * @package TourFacil\Core\Models
 */
class CampoAdicionalServico extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'servico_id',
        'campo',
        'placeholder',
        'obrigatorio',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    /**
     * @param $campo
     * @return string
     */
    public function setCampoAttribute($campo)
    {
        return $this->attributes['campo'] = formatarNome($campo);
    }

    /**
     * @param $placeholder
     * @return string
     */
    public function setPlaceholderAttribute($placeholder)
    {
        return $this->attributes['placeholder'] = ucfirst(mb_strtolower($placeholder));
    }

    /**
     * Retorna se o campo está ativo ou não
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
    public function getIsRequiredAttribute()
    {
        return ($this->attributes['obrigatorio'] == CampoAdicionalEnum::REQUIRIDO);
    }
}
