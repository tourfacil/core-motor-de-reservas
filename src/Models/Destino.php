<?php namespace TourFacil\Core\Models;

use Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Enum\FotoServicoEnum;
use TourFacil\Core\Enum\ServicoEnum;
use TourFacil\Core\Traits\HasSlug;

/**
 * Class Destino
 * @package TourFacil\Core\Models
 */
class Destino extends Model
{
    use SoftDeletes, HasSlug;

    public $default_quality = 75;

    /**
     * Preset da foto
     *
     * @var array
     */
    static $PHOTO_PRESET = [
        FotoServicoEnum::LARGE => ["width" => 550]
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'nome',
        'foto',
        'canal_venda_id',
        'descricao_curta',
        'descricao_completa',
        'valor_minimo',
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
        'fotoDestino'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function canalVenda()
    {
        return $this->belongsTo(CanalVenda::class)
            ->orderBy('created_at', 'desc');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function categorias()
    {
        return $this->hasMany(Categoria::class)
            ->orderBy('nome');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servicos()
    {
        return $this->hasMany(Servico::class)->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function homeDestino()
    {
        return $this->hasMany(HomeDestino::class)
            ->orderBy('ordem');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servicosAtivos()
    {
        return $this->hasMany(Servico::class)
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
     * URL da imagem
     *
     * @return string
     */
    public function getFotoDestinoAttribute()
    {
        // Logo que cadastra nao existe a casa foto
        if(isset($this->attributes['foto'])) {

            $image = json_decode($this->attributes['foto'], true);

            $image = $image[FotoServicoEnum::LARGE] ?? Arr::first($image);

            return env('CDN_URL') . $image . "?quality=" . $this->default_quality;
        }

        return "";
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
}
