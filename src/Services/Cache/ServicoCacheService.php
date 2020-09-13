<?php namespace TourFacil\Core\Services\Cache;

use TourFacil\Core\Enum\CategoriasEnum;
use TourFacil\Core\Enum\ServicoEnum;
use TourFacil\Core\Models\Categoria;
use TourFacil\Core\Models\Servico;

/**
 * Class ServicoCacheService
 * @package TourFacil\Core\Services\Cache
 */
class ServicoCacheService extends DefaultCacheService
{
    /**
     * Prefixo do cache
     *
     * @var string
     */
    protected static $prefix_cache = "servicos_";

    /**
     * Tag default para o cache
     *
     * @var string
     */
    protected static $tag = "servicos_";

    /**
     * Recupera os servicos da categoria
     *
     * @param $categoria_id
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function servicosCategoria($categoria_id, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $categoria_id, function () use ($categoria_id, $canal_id) {
            return Categoria::with('servicosAtivos.fotoPrincipal', 'servicosAtivos.secoesCategoria')
                ->whereHas('destino', function ($query) use ($canal_id) {
                    return $query->where('canal_venda_id', $canal_id);
                })->find($categoria_id);
        });
    }

    /**
     * Lista de serviços para o feed do facebook
     *
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function servicosFeedFacebook($cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $canal_id, function () use ($canal_id) {
            return Servico::with([
                'fotoPrincipal',
                'destino' => function($q) {
                    return $q->select(['id', 'nome', 'slug']);
                },
                'categoria' => function($q) {
                    return $q->select(['id', 'nome', 'slug']);
                },
            ])->where([
                'canal_venda_id' => $canal_id,
                'status' => ServicoEnum::ATIVO
            ])->get([
                'id', 'destino_id', 'slug', 'uuid', 'nome', 'descricao_curta', 'valor_venda', 'cidade'
            ]);
        });
    }

    /**
     * Servicos da categoria
     *
     * @param $categoria_id
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function servicosCategoriaSite($categoria_id, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $categoria_id, function () use ($categoria_id, $canal_id) {
            return Servico::with([
                'fotoPrincipal',
                'ranking' => function($q) {
                    return $q->select(['servico_id', 'ranking']);
                },
                'secoesCategoria' => function($query) use ($categoria_id) {
                    return $query->where('categoria_id', $categoria_id);
                }
            ])->whereHas('categorias', function ($query) use ($categoria_id) {
                return $query->where('categorias.id', $categoria_id);
            })->where(['canal_venda_id' => $canal_id, 'status' => ServicoEnum::ATIVO])->get([
                'id', 'slug', 'uuid', 'nome', 'valor_venda', 'cidade'
            ]);
        });
    }

    /**
     * Detalhes do serviço
     *
     * @param $servico_id
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function detalheServico($servico_id, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $servico_id, function () use ($servico_id, $canal_id) {
            return Servico::with('fotos')->find($servico_id);
        });
    }

    /**
     * Detalhes do servico baseado no Slug
     *
     * @param $servico_slug
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function servicoSlug($servico_slug, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $servico_slug, function () use ($servico_slug, $canal_id) {
            return Servico::with([
                'fotos',
                'fornecedor' => function($q) {
                    return $q->select(['id', 'termos', 'nome_fantasia']);
                },
                'categorias' => function($q) {
                    return $q->select([
                        'categoria_servico.servico_id',
                        'categoria_servico.categoria_id',
                        'categoria_servico.padrao',
                        'categorias.id',
                        'categorias.slug',
                    ]);
                }
            ])->where([
                'slug' => $servico_slug,
                'canal_venda_id' => $canal_id
            ])->first();
        });
    }

    /**
     * Recupera os servicos por ID
     *
     * @param array $ids
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function detalheServicosId(array $ids, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        // Chave com os servicos id
        $key = implode("_", $ids);

        return self::run($cache, __FUNCTION__ . $key, function () use ($ids, $canal_id) {
            return Servico::with([
                'fotoPrincipal',
                'categoria' => function($q) {
                    return $q->select(['id', 'slug']);
                },
                'destino' => function($f) {
                    return $f->select(['id', 'slug']);
                },
                'tags:servico_id,icone,descricao'
            ])->where([
                'canal_venda_id' => $canal_id,
                'status' => ServicoEnum::ATIVO
            ])->whereIn('id', $ids)->get([
                'id', 'destino_id', 'slug', 'uuid', 'nome', 'valor_venda', 'cidade'
            ]);
        });
    }

    /**
     * Servicos relacionados por servico e categoria
     *
     * @param $servico_id
     * @param $categoria_id
     * @param int $limit
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function servicosRelacionados($servico_id, $categoria_id, $limit = 10, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $servico_id . "cat" . $categoria_id, function () use ($servico_id, $categoria_id, $canal_id, $limit) {
            return Servico::with('fotoPrincipal')->whereHas('categorias', function ($query) use ($categoria_id) {
                return $query->where('categorias.id', $categoria_id);
            })->where('servicos.id', '<>', $servico_id)
                ->where(['status'=> ServicoEnum::ATIVO, 'canal_venda_id' => $canal_id])
                ->inRandomOrder()->limit($limit)->get([
                    'id', 'slug', 'uuid', 'nome', 'valor_venda', 'cidade'
                ]);
        });
    }
}
