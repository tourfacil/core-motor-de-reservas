<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class UsuarioFornecedor
 * @package TourFacil\Core\Models
 */
class UsuarioFornecedor extends Authenticatable
{
    use SoftDeletes, Notifiable;

    /**
     * @var string
     */
    protected $table = "usuario_fornecedores";

    /**
     * @var array
     */
    protected $fillable = [
        "fornecedor_id",
        "nome",
        "email",
        "password",
        "level"
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class);
    }

    /**
     * Retorna se o usuario está ativo ou não
     *
     * @return bool
     */
    public function getStatusAttribute()
    {
        return ($this->attributes['deleted_at'] == null);
    }

    /**
     * @param $nome
     * @return string
     */
    public function setNomeAttribute($nome)
    {
        return $this->attributes['nome'] = formatarNome($nome);
    }

    /**
     * @param $email
     * @return mixed|null|string|string[]
     */
    public function setEmailAttribute($email)
    {
        return $this->attributes['email'] = mb_strtolower($email);
    }
}
