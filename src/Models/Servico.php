<?php namespace TourFacil\Core\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use TourFacil\Core\Enum\CanaisVendaEnum;
use TourFacil\Core\Enum\CategoriasEnum;
use TourFacil\Core\Enum\Descontos\StatusDesconto;
use TourFacil\Core\Enum\FotoServicoEnum;
use TourFacil\Core\Enum\ServicoEnum;
use TourFacil\Core\Traits\HasSlug;
use TourFacil\Core\Traits\HasUuid;

/**
 * Class Servico
 * @package TourFacil\Core\Models
 */
class Servico extends Model
{
    use SoftDeletes, HasUuid, HasSlug;

    /**
     * Preset da foto
     *
     * @var array
     */
    static $PHOTO_PRESET = [
        FotoServicoEnum::SMALL => ["width" => 88, "height" => 59],
        FotoServicoEnum::MEDIUM => ["width" => 290, "height" => 400],
        FotoServicoEnum::LARGE => ["width" => 800, "height" => 600]
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'destino_id',
        'fornecedor_id',
        'canal_venda_id',
        'nome',
        'valor_venda',
        'comissao_afiliado',
        'antecedencia_venda',
        'hora_maxima_antecedencia',
        'tipo_corretagem',
        'corretagem',
        'horario',
        'titulo_pagina',
        'titulo_servico',
        'descricao_curta',
        'descricao_completa',
        'regras',
        'observacao_voucher',
        'palavras_chaves',
        'info_clientes',
        'integracao',
        'localizacao',
        'cidade',
        'status',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function canalVenda()
    {
        return $this->belongsTo(CanalVenda::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fornecedor()
    {
        return $this->belongsTo(Fornecedor::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destino()
    {
        return $this->belongsTo(Destino::class)->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function agendaServico()
    {
        return $this->belongsToMany(AgendaServico::class, 'agenda_has_servico');
    }

    /** Carrega o relacionamento com agenda */
    public function loadAgendaServico()
    {
        $this->setRelation('agenda', $this->agendaServico->first());
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany|\Illuminate\Database\Query\Builder
     */
    public function categoria()
    {
        return $this->belongsToMany(Categoria::class)->withPivot('padrao')
            ->wherePivot('padrao', CategoriasEnum::CATEGORIA_PADRAO)->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categorias()
    {
        return $this->belongsToMany(Categoria::class)
            ->withPivot('padrao')->orderBy('padrao')->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function secoesCategoria()
    {
        return $this->belongsToMany(SecaoCategoria::class)->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function camposAdicionais()
    {
        return $this->hasMany(CampoAdicionalServico::class)
            ->orderBy('deleted_at')
            ->orderBy('obrigatorio')->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function camposAdicionaisAtivos()
    {
        return $this->hasMany(CampoAdicionalServico::class)
            ->orderBy('deleted_at')->orderBy('obrigatorio');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fotos()
    {
        return $this->hasMany(FotoServico::class)->orderBy('tipo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function fotoPrincipal()
    {
        return $this->hasOne(FotoServico::class)
            ->where('tipo', FotoServicoEnum::PRINCIPAL);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variacaoServico()
    {
        return $this->hasMany(VariacaoServico::class)
            ->orderBy("percentual", "DESC")->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variacaoServicoAtivas()
    {
        return $this->hasMany(VariacaoServico::class)
            ->orderBy("percentual", "DESC");
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reservas()
    {
        return $this->hasMany(ReservaPedido::class)->latest();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ranking()
    {
        return $this->hasOne(RankingServico::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags()
    {
        return $this->hasMany(TagServico::class)->orderBy('ordem');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tagsInternas()
    {
        return $this->hasMany(TagServicoInterno::class)->orderBy('ordem');
    }

    /**
     * Retira a casa da relação com categoria
     *
     * @return null
     */
    public function getCategoriaAttribute()
    {
        // Categoria padrao
        if($this->relationLoaded('categoria'))
            return $this->getRelation('categoria')[0];

        // Primeira categoria
        if($this->relationLoaded('categorias'))
            return $this->getRelation('categorias')[0];

        return null;
    }

    /**
     * Agenda do servico
     *
     * @return mixed
     */
    public function getAgendaAttribute()
    {
        if (!array_key_exists('agenda', $this->relations)) $this->loadAgendaServico();

        return $this->getRelation('agenda');
    }

    /**
     * @return mixed
     */
    public function getStatusServicoAttribute()
    {
        return ServicoEnum::STATUS_SERVICO[$this->attributes['status']];
    }

    /**
     * @return mixed
     */
    public function getCorStatusServicoAttribute()
    {
        return ServicoEnum::CORES_STATUS[$this->attributes['status']];
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
     * @param $cidade
     * @return string
     */
    public function setCidadeAttribute($cidade)
    {
        return $this->attributes['cidade'] = formatarNome($cidade);
    }

    /**
     * @param $valor_venda
     * @return mixed
     */
    public function setValorVendaAttribute($valor_venda)
    {
        // Caso esteja no formato BR
        if(Str::contains($valor_venda, ',')) {
            $valor_venda = str_replace(",", ".", str_replace(".", "", $valor_venda));
        }

        return $this->attributes['valor_venda'] = $valor_venda;
    }

    /**
     * @param $corretagem
     * @return mixed
     */
    public function setCorretagemAttribute($corretagem)
    {
        // Caso seja falor fixo
        if(Str::contains($corretagem, ',')) {
            $corretagem = str_replace(",", ".", str_replace(".", "", $corretagem));
        }

        return $this->attributes['corretagem'] = $corretagem;
    }

    /**
     * Relação que retorna todos os descontos daquele produto
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function descontos() {
        return $this->hasMany(Desconto::class);
    }

    public function avaliacoes() {
        return $this->hasMany(AvaliacaoServico::class);
    }

    /**
     * Retorna o desconto ativo do produto
     * Caso tenha mais de um desconto ativo para a data, ele irá retornar o último
     *
     * @return mixed|null
     */
    public function getDescontoAtivoAttribute() {

        // Busca o dia de hoje
        $hoje = Carbon::now();

        // Busca todos os descontos ativos do serviço
        $descontos = $this->descontos()
                          ->where('status', StatusDesconto::ATIVO)
                          ->get();

        // Lista para guardar todos os descontos ativos para hoje
        $descontos_validos = [];

        // Percorre todos os descontos do produto
        foreach ($descontos as $desconto) {
            if($hoje->between(Carbon::parse($desconto->inicio), Carbon::parse($desconto->final))) {
                $descontos_validos[] = $desconto;
            }
        }

        // Caso não haja desconto ativado, retorna NULL
        if(count($descontos_validos) == 0) {
            return null;
        }

        // Caso tenha desconto ativado, retorna o último
        return $descontos_validos[count($descontos_validos) - 1];
    }
}
