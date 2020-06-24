<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PagamentoTerminal
 * @package TourFacil\Core\Models
 */
class PagamentoTerminal extends Model
{
    /**
     * @var string
     */
    protected $table = "pagamento_terminais";

    /**
     * @var array
     */
    protected $fillable = [
        "terminal_id",
        "mes_referencia",
        "mes_pagamento",
        "total_comissao",
        "total_pago",
        "data_pagamento",
    ];

    /**
     * @var array
     */
    protected $dates = [
        "mes_referencia",
        "mes_pagamento"
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function terminal()
    {
        return $this->belongsTo(Terminal::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function comissoesPagamento()
    {
        return $this->belongsToMany(ComissaoTerminal::class, 'comissao_pagamento_terminais');
    }
}
