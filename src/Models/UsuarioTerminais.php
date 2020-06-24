<?php namespace TourFacil\Core\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Class UsuarioTerminais
 * @package TourFacil\Core\Models
 */
class UsuarioTerminais extends Authenticatable
{
    use SoftDeletes, Notifiable;

    /**
     * @var string
     */
    protected $table = "usuario_terminais";

    /**
     * @var array
     */
    protected $fillable = [
        "terminal_id",
        "nome",
        "email",
        "password",
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
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function terminal()
    {
        return $this->belongsTo(Terminal::class)->withTrashed();
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
