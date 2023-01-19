<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use TourFacil\Core\Traits\HasUuid;

/**
 * Class Fornecedor
 * @package TourFacil\Core\Models
 */
class Fornecedor extends Model
{
    use HasUuid, SoftDeletes, Notifiable;

    /** @var string */
    protected $table = "fornecedores";

    /**
     * @var array
     */
    protected $fillable = [
        "cnpj",
        "razao_social",
        "nome_fantasia",
        "responsavel",
        "email_responsavel",
        "telefone_responsavel",
        "cep",
        "endereco",
        "bairro",
        "cidade",
        "estado",
        "email",
        "telefone",
        "site",
        "termos",
        'tipo_fatura',
        'tipo_periodo_fatura',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function dadosBancarios()
    {
        return $this->hasOne(DadosBancariosFornecedor::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function splits()
    {
        return $this->hasMany(SplitFornecedor::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usuarios()
    {
        return $this->hasMany(UsuarioFornecedor::class);
    }

    public function reservas()
    {
        return $this->hasMany(ReservaPedido::class);
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
     * Retorna os emails do fornecedor
     *
     * @return array
     */
    public function getEmailsAttribute()
    {
        return array_map('trim', explode(',', $this->attributes['email']));
    }

    /**
     * Route notifications for the Mail channel.
     *
     * @param $notification
     * @return array
     */
    public function routeNotificationForMail($notification)
    {
        return $this->getEmailsAttribute();
    }
}
