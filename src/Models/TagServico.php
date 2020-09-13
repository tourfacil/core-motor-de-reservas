<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class TagServico
 * @package TourFacil\Core\Models
 */
class TagServico extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        "servico_id",
        "ordem",
        "icone",
        "descricao",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }
}
