<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RankingServico
 * @package TourFacil\Core\Models
 */
class RankingServico extends Model
{
    /**
     * @var array
     */
    protected $fillable = [
        "servico_id",
        "ranking",
        "vendido",
        "avaliacoes",
    ];

    /**
     * @var array
     */
    protected $casts = [
        "vendido" => "object",
        "avaliacoes" => "object",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function servico()
    {
        return $this->belongsTo(Servico::class);
    }
}
