<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Traits\HasUuid;

/**
 * Class CanalVenda
 * @package TourFacil\Core\Models
 */
class CanalVenda extends Model
{
    use SoftDeletes, HasUuid;

    /**
     * @var array
     */
    protected $fillable = [
        'nome',
        'site',
        'maximo_parcelas',
        'parcelas_sem_juros',
        'juros_parcela',
    ];

    /**
     * Retira protocolos do site
     *
     * @param $site
     */
    public function setSiteAttribute($site)
    {
        $this->attributes['site'] = preg_replace('/(^\w+:|^)\/\//', '', $site);
    }

    /**
     * Retorna se o canal está ativo ou não
     *
     * @return bool
     */
    public function getStatusAttribute()
    {
        return ($this->attributes['deleted_at'] == null);
    }
}
