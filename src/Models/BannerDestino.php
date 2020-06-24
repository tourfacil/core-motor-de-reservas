<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Enum\BannerEnum;
use TourFacil\Core\Enum\ServicoEnum;

/**
 * Class BannerDestino
 * @package TourFacil\Core\Models
 */
class BannerDestino extends Model
{
    use SoftDeletes;

    /** @var int  */
    protected $default_quality = 75;

    /**
     * @var array
     */
    protected $fillable = [
        'destino_id',
        'servico_id',
        'ordem',
        'titulo',
        'descricao',
        'banner',
        'tipo',
    ];

    /**
     * Preset da foto
     *
     * @var array
     */
    static $PHOTO_PRESET = [
        "width" => 1980, "height" => 650,
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function servicoAtivo()
    {
        return $this->belongsTo(Servico::class, 'servico_id')
            ->where('status', ServicoEnum::ATIVO)->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destino()
    {
        return $this->belongsTo(Destino::class);
    }

    /**
     * @return mixed
     */
    public function getTipoBannerAttribute()
    {
        return BannerEnum::TIPOS[$this->attributes['tipo']];
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
     * Foto grande do serviço
     *
     * @return string
     */
    public function getBannerUrlAttribute()
    {
        // Logo que cadastra nao existe a casa foto
        if(isset($this->attributes['banner'])) {
            return env('CDN_URL') . $this->attributes['banner'] . "?quality=" . $this->default_quality;
        }

        return "";
    }

    /**
     * @param $descricao
     * @return string
     */
    public function setDescricaoAttribute($descricao)
    {
        return $this->attributes['descricao'] = ucfirst(mb_strtolower($descricao));
    }
}
