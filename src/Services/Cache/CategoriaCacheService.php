<?php namespace TourFacil\Core\Services\Cache;

use TourFacil\Core\Enum\ServicoEnum;
use TourFacil\Core\Models\Categoria;
use TourFacil\Core\Models\SecaoCategoria;

/**
 * Class CategoriaCacheService
 * @package TourFacil\Core\Services\Cache
 */
class CategoriaCacheService extends DefaultCacheService
{
    /**
     * Prefixo do cache
     *
     * @var string
     */
    protected static $prefix_cache = "categorias_";

    /**
     * Tag default para o cache
     *
     * @var string
     */
    protected static $tag = "categorias";

    /**
     * Recupera as categorias do destino
     *
     * @param $destino_id
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function categoriasDestino($destino_id, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $destino_id, function () use ($destino_id, $canal_id) {
            return Categoria::where('destino_id', $destino_id)->whereHas('destino', function ($query) use ($canal_id) {
                return $query->where('canal_venda_id', $canal_id);
            })->whereHas('servicosAtivos')->orderBy('posicao_menu')->get();
        });
    }

    /**
     * Recupera as categorias do destino pelo slug
     *
     * @param $destino_slug
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function categoriasDestinoSlugSite($destino_slug, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $destino_slug, function () use ($destino_slug, $canal_id) {
            return Categoria::with(['servicos' => function($q) {
                return $q->where('status', ServicoEnum::ATIVO)->select(['servicos.nome', 'servicos.slug'])->orderBy('servicos.nome');
            }])->whereHas('servicosAtivos')->whereHas('destino', function ($query) use ($destino_slug, $canal_id) {
                return $query->where(['canal_venda_id' => $canal_id, 'slug' => $destino_slug]);
            })->orderBy('posicao_menu')->get(['nome', 'slug', 'categorias.id']);
        });
    }

    /**
     * Detalhes da categoria
     *
     * @param $destino
     * @param $categoria_slug
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function categoriaSlug($destino, $categoria_slug, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $destino->id . $categoria_slug, function () use ($categoria_slug, $destino, $canal_id) {
            return Categoria::whereHas('destino', function ($query) use ($canal_id) {
                return $query->where('canal_venda_id', $canal_id);
            })->where([
                'slug' => $categoria_slug,
                'destino_id' => $destino->id
            ])->first();
        });
    }

    /**
     * Secoes categoria que possuem servicos
     *
     * @param $categoria_id
     * @param bool $cache
     * @return mixed
     */
    public static function secoesCategoria($categoria_id, $cache = true)
    {
        return self::run($cache, __FUNCTION__ . $categoria_id, function () use ($categoria_id) {
            return SecaoCategoria::whereHas('servicosAtivos')->where('categoria_id', $categoria_id)->get();
        });
    }
}
