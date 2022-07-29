<?php namespace TourFacil\Core\Services\Cache;

use TourFacil\Core\Models\CanalVenda;

/**
 * Class CanalVendaCacheService
 * @package TourFacil\Core\Services\Cache
 */
class CanalVendaCacheService extends DefaultCacheService
{
    /**
     * Prefixo do cache
     *
     * @var string
     */
    protected static $prefix_cache = "canalvenda_";

    /**
     * Tag default para o cache
     *
     * @var string
     */
    protected static $tag = "canalvenda";

    /**
     * Canal de venda utilizando o .env
     *
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function canalvendaSite($cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $canal_id, function () use ($canal_id) {
            return CanalVenda::select(['id', 'parcelas_sem_juros', 'maximo_parcelas'])->find($canal_id);
        });
    }
}
