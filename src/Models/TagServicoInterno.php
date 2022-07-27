<?php

namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

class TagServicoInterno extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        "servico_id",
        "ordem",
        "icone",
        "titulo",
        "descricao",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function servico() {
        return $this->belongsTo(Servico::class);
    }
}
