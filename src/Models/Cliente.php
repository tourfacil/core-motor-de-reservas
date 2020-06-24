<?php namespace TourFacil\Core\Models;

use App\Notifications\ResetPasswordNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use TourFacil\Core\Traits\HasUuid;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Class Cliente
 * @package TourFacil\Core\Models
 */
class Cliente extends Authenticatable
{
    use SoftDeletes, HasUuid, Notifiable;

    /**
     * @var array
     */
    protected $fillable = [
        'canal_venda_id',
        'nome',
        'email',
        'cpf',
        'nascimento',
        'telefone',
        'password',
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
     * @var array
     */
    protected $dates = [
        "nascimento",
        "deleted_at"
    ];

    /**
     * Route notifications for the Mail channel.
     *
     * @param $notification
     * @return array
     */
    public function routeNotificationForMail($notification)
    {
        return [$this->attributes['email'] => $this->attributes['nome']];
    }

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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pedidos()
    {
        return $this->hasMany(Pedido::class)->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function canalVenda()
    {
        return $this->belongsTo(CanalVenda::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function reservas()
    {
        return $this->belongsToMany(ReservaPedido::class, 'pedidos',
            'cliente_id', 'id', null, 'pedido_id')->latest();
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
     * @return bool|mixed|null|string|string[]
     */
    public function setEmailAttribute($email)
    {
        return $this->attributes['email'] = mb_strtolower($email);
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
            $birthdate->year('19' . $year{2} . $year{3});
        }

        return $this->attributes['nascimento'] = $birthdate->format('Y-m-d');
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
