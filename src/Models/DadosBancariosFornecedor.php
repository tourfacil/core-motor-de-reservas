<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DadosBancariosFornecedor
 * @package TourFacil\Core\Models
 */
class DadosBancariosFornecedor extends Model
{
    /**
     * @var string
     */
    protected $table = "dados_bancarios_fornecedores";

    /**
     * @var array
     */
    protected $fillable = [
        'fornecedor_id',
        'banco',
        'agencia',
        'conta',
        'tipo_conta',
        'observacoes',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }
}
