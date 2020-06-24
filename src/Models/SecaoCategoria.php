<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Enum\ServicoEnum;

/**
 * Class SecaoCategoria
 * @package TourFacil\Core\Models
 */
class SecaoCategoria extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        'nome',
        'categoria_id',
    ];

    /** Array necessario para cadastrar */
    const ARRAY_STORE = [
        'nome',
        'categoria_id',
    ];

    /** Array necessario para atualizar */
    const ARRAY_UPDATE = ['nome'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
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
}
