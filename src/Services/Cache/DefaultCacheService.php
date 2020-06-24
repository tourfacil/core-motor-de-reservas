<?php namespace TourFacil\Core\Services\Cache;

use Cache;
use Exception;

/**
 * Class DefaultCacheService
 * @package TourFacil\Core\Services\Cache
 */
class DefaultCacheService
{
    /**
     * Prefixo do cache
     *
     * @var string
     */
    protected static $prefix_cache = "";

    /**
     * Tag default para o cache
     *
     * @var string
     */
    protected static $tag = "";

    /**
     * Tempo para expirar o cache 1 dia
     *
     * @var float|int
     */
    public static $expires_in = 60 * 24;

    /**
     * Recupera o canal de venda do env
     *
     * @return mixed
     * @throws Exception
     */
    public static function getCanalVenda()
    {
        $canal_venda_id = env("CANAL_VENDA_ID");

        if(is_null($canal_venda_id)) {
            throw new Exception("CANAL_VENDA_ID não configurado no .ENV");
        }

        return (int) $canal_venda_id;
    }

    /**
     * Execute query
     *
     * @param $cache
     * @param $func
     * @param callable $query
     * @return mixed
     */
    protected static function run($cache, $func, callable $query)
    {
        if($cache) {
            $expires = now()->addMinutes(self::$expires_in);
            return Cache::tags(self::$tag)->remember(self::$prefix_cache . $func, $expires, function () use ($query) {
                return $query();
            });
        }

        return $query();
    }

    /**
     * Limpa o cache das categorias
     *
     * @return bool
     */
    public static function flushCache()
    {
        return Cache::tags(self::$tag)->flush();
    }
}
