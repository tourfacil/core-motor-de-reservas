<?php namespace TourFacil\Core\Services\Cache;

use TourFacil\Core\Models\BannerDestino;

/**
 * Class BannerCacheService
 * @package TourFacil\Core\Services\Cache
 */
class BannerCacheService extends DefaultCacheService
{
    /**
     * Prefixo do cache
     *
     * @var string
     */
    protected static $prefix_cache = "banners_";

    /**
     * Tag default para o cache
     *
     * @var string
     */
    protected static $tag = "banners";

    /**
     * Retorna os banners do destino
     *
     * @param $destino_id
     * @param bool $cache
     * @return mixed
     */
    public static function bannersDestino($destino_id, $cache = true)
    {
        return self::run($cache, __FUNCTION__ . $destino_id, function () use ($destino_id) {
            return BannerDestino::with([
                'servicoAtivo.categoria' => function ($q) {
                    return $q->select(['id', 'uuid', 'nome', 'slug']);
                },
                'servicoAtivo' => function ($q) {
                    return $q->select(['id', 'uuid', 'nome', 'slug', 'valor_venda']);
                }
            ])->whereHas('servicoAtivo')
                ->where('destino_id', $destino_id)
                ->orderBy('ordem')->get();
        });
    }
}
