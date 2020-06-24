<?php namespace TourFacil\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use TourFacil\Core\Enum\TerminaisEnum;
use TourFacil\Core\Traits\HasCodigo;
use TourFacil\Core\Traits\HasPdvId;

/**
 * Class Terminal
 * @package TourFacil\Core\Models
 */
class Terminal extends Model
{
    use SoftDeletes, HasCodigo, HasPdvId;

    /**
     * @var string
     */
    protected $table = "terminais";

    /**
     * @var array
     */
    protected $fillable = [
        "nome",
        "pdv_id",
        "identificacao",
        "fabricante",
        "fornecedor_id",
        "destino_id",
        "nome_responsavel",
        "email_responsavel",
        "telefone_responsavel",
        "endereco_mapa",
        "nome_local",
        "endereco",
        "cidade",
        "cep",
        "estado",
        "geolocation",
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usuarios()
    {
        return $this->hasMany(UsuarioTerminais::class)
            ->latest()->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destino()
    {
        return $this->belongsTo(Destino::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function historicoConexao()
    {
        return $this->hasMany(HistoricoConexaoTerminal::class)->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function debitoComissao()
    {
        return $this->hasMany(DebitoComissaoTerminal::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comissaoTerminal()
    {
        return $this->hasMany(ComissaoTerminal::class)->latest();
    }

    /**
     * @return mixed
     */
    public function getLatitudeAttribute()
    {
        $location = explode(",", $this->geolocation);

        return $location[0];
    }

    /**
     * @return mixed
     */
    public function getLongitudeAttribute()
    {
        $location = explode(",", $this->geolocation);

        return $location[1];
    }

    /**
     * Retorna se o terminal está ativo ou não
     *
     * @return bool
     */
    public function getStatusAttribute()
    {
        return ($this->attributes['deleted_at'] == null);
    }

    /**
     * @return mixed
     */
    public function getNomeFabricanteAttribute()
    {
        return TerminaisEnum::FABRICANTES[$this->attributes['fabricante']];
    }
}
