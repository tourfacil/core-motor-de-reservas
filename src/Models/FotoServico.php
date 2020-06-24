<?php namespace TourFacil\Core\Models;

use Arr;
use Illuminate\Database\Eloquent\Model;
use TourFacil\Core\Enum\FotoServicoEnum;

/**
 * Class FotoServico
 * @package TourFacil\Core\Models
 */
class FotoServico extends Model
{
    public $default_quality = 75;

    /**
     * @var array
     */
    protected $fillable = [
        'servico_id',
        'foto',
        'legenda',
        'tipo',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'foto' => 'array'
    ];

    /**
     * @var array
     */
    protected $appends = [
        'FotoLarge',
        'FotoMedium',
        'FotoSmall',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }

    /**
     * Foto grande do serviço
     *
     * @return string
     */
    public function getFotoLargeAttribute()
    {
        // Logo que cadastra nao existe a casa foto
        if(isset($this->attributes['foto'])) {

            $imagens = json_decode($this->attributes['foto'], true);

            $image = $imagens[FotoServicoEnum::LARGE] ?? Arr::first($imagens);

            return env('CDN_URL') . $image . "?quality=" . $this->default_quality;
        }

        return "";
    }

    /**
     * Foto média do serviço
     *
     * @return string
     */
    public function getFotoMediumAttribute()
    {
        // Logo que cadastra nao existe a casa foto
        if(isset($this->attributes['foto'])) {

            $imagens = json_decode($this->attributes['foto'], true);

            $image = $imagens[FotoServicoEnum::MEDIUM] ?? Arr::first($imagens);

            return env('CDN_URL') . $image . "?quality=" . $this->default_quality;
        }

        return "";
    }

    /**
     * Foto pequena do serviço
     *
     * @return string
     */
    public function getFotoSmallAttribute()
    {
        // Logo que cadastra nao existe a casa foto
        if(isset($this->attributes['foto'])) {

            $imagens = json_decode($this->attributes['foto'], true);

            $image = $imagens[FotoServicoEnum::SMALL] ?? Arr::first($imagens);

            return env('CDN_URL') . $image . "?quality=" . $this->default_quality;
        }

        return "";
    }
}
