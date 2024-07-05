<?php namespace TourFacil\Core\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class DadoClienteReservaPedido
 * @package TourFacil\Core\Models
 */
class DadoClienteReservaPedido extends Model
{
    use SoftDeletes;

    /**
     * @var array
     */
    protected $fillable = [
        'variacao_servico_id',
        'reserva_pedido_id',
        'nome',
        'documento',
        'nascimento',
    ];

    /**
     * @var array
     */
    protected $dates = [
        "nascimento",
        "deleted_at",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reservaPedido()
    {
        return $this->belongsTo(ReservaPedido::class);
    }

    /**
     * @return mixed
     */
    public function variacaoServico()
    {
        return $this->belongsTo(VariacaoServico::class)->withTrashed();
    }

    /**
     * Data de nascimento do cliente
     *
     * @param $nascimento
     * @return bool|\DateTime
     */
    public function setNascimentoAttribute($nascimento)
    {
        $birthdate = Carbon::createFromFormat('d/m/Y', $nascimento);

        // Caso a data seja maior que 100 anos provavelmente foi colocado errado
        if($birthdate->diffInYears() >= 100) {
            // Recupera a ano informado
            $year = (string) $birthdate->year;
            // Coloca 19 e concatena os ultimos dois anos
            $birthdate->year('19' . $year[2] . $year[3]);
        }

        return $this->attributes['nascimento'] = $birthdate;
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
