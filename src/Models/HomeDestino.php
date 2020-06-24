<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Enum\ServicoEnum;
use TourFacil\Core\Enum\TipoHomeDestinoEnum;

/**
 * Class HomeDestino
 * @package TourFacil\Core\Models
 */
class HomeDestino extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        "destino_id",
        "ordem",
        "titulo",
        "descricao",
        "tipo",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destino()
    {
        return $this->belongsTo(Destino::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servicos()
    {
        return $this->belongsToMany(Servico::class)
            ->withPivot('ordem')->withTimestamps()
            ->orderBy('home_destino_servico.ordem');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servicosAtivos()
    {
        return $this->belongsToMany(Servico::class)
            ->where('status', ServicoEnum::ATIVO)
            ->withPivot('ordem')->withTimestamps()
            ->orderBy('home_destino_servico.ordem');
    }

    /**
     * @return mixed
     */
    public function getTipoHomeAttribute()
    {
        return TipoHomeDestinoEnum::TIPOS_HOME_DESTINO[$this->attributes['tipo']];
    }

    /**
     * Retorna se o fornecedor está ativo ou não
     *
     * @return bool
     */
    public function getStatusAttribute()
    {
        return ($this->attributes['deleted_at'] == null);
    }

    /**
     * @param $titulo
     * @return string
     */
    public function setTituloAttribute($titulo)
    {
        return $this->attributes['titulo'] = ucfirst($titulo);
    }

    /**
     * @param $descricao
     * @return string
     */
    public function setDescricaoAttribute($descricao)
    {
        return $this->attributes['descricao'] = ucfirst($descricao);
    }
}
