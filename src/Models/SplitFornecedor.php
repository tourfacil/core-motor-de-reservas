<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class SplitFornecedor
 * @package TourFacil\Core\Models
 */
class SplitFornecedor extends Model
{
    /** @var string */
    protected $table = "split_fornecedores";

    /**
     * @var array
     */
    protected $fillable = [
        "fornecedor_id",
        "canal_venda_id",
        "token",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function canalVenda()
    {
        return $this->belongsTo(CanalVenda::class);
    }
}
