<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Enum\FotoServicoEnum;
use TourFacil\Core\Enum\ServicoEnum;
use TourFacil\Core\Traits\HasSlug;
use TourFacil\Core\Traits\HasUuid;

/**
 * Class Categoria
 * @package TourFacil\Core\Models
 */
class Categoria extends Model
{
    use HasUuid, HasSlug, SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'destino_id',
        'nome',
        'descricao',
        'titulo_pagina',
        'foto',
        'valor_minimo',
        'posicao_menu',
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
        'bannerCategoria',
        'fotoCategoria'
    ];

    /** Array necessario para atualizar */
    const ARRAY_UPDATE = [
        'nome',
        'descricao',
        'titulo_pagina',
        'posicao_menu',
        'tipo'
    ];

    /**
     * Preset da foto
     *
     * @var array
     */
    static $PHOTO_PRESET = [
        FotoServicoEnum::LARGE => ["width" => 1980, "height" => 360], // Banner
        FotoServicoEnum::MEDIUM => ["width" => 500], // Foto destaque
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destino()
    {
        return $this->belongsTo(Destino::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function secoesCategoria()
    {
        return $this->hasMany(SecaoCategoria::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servicos()
    {
        return $this->belongsToMany(Servico::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servicosAtivos()
    {
        return $this->belongsToMany(Servico::class)
            ->where('status', ServicoEnum::ATIVO)->latest();
    }

    /**
     * @param $nome
     * @return string
     */
    public function setNomeAttribute($nome)
    {
        return $this->attributes['nome'] = formatarNome($nome);
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
     * Url do banner da categoria
     *
     * @return string
     */
    public function getBannerCategoriaAttribute()
    {
        // Logo que cadastra nao existe a casa foto
        if(isset($this->attributes['foto'])) {

            $banner = json_decode($this->attributes['foto'], true);

            $banner = $banner[FotoServicoEnum::LARGE] ?? array_first($banner);

            return env('CDN_URL') . $banner;
        }

        return "";
    }

    /**
     * Url da foto da categoria
     *
     * @return string
     */
    public function getFotoCategoriaAttribute()
    {
        // Logo que cadastra nao existe a casa foto
        if(isset($this->attributes['foto'])) {

            $banner = json_decode($this->attributes['foto'], true);

            $banner = $banner[FotoServicoEnum::MEDIUM] ?? array_first($banner);

            return env('CDN_URL') . $banner;
        }

        return "";
    }
}
