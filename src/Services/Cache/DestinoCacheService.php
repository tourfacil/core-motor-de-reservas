<?php namespace TourFacil\Core\Services\Cache;

use Cache;
use DB;
use TourFacil\Core\Enum\CategoriasEnum;
use TourFacil\Core\Enum\ServicoEnum;
use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Models\Destino;
use TourFacil\Core\Models\Servico;

/**
 * Class DestinoCacheService
 * @package TourFacil\Core\Services\Cache
 */
class DestinoCacheService extends DefaultCacheService
{
    /**
     * Prefixo do cache
     *
     * @var string
     */
    protected static $prefix_cache = "destinos_";

    /**
     * Tag default para o cache
     *
     * @var string
     */
    protected static $tag = "destinos";

    /**
     * Recupera as informacoes do destino
     *
     * @param $destino_slug
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function destinoSlug($destino_slug, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $destino_slug, function () use ($destino_slug, $canal_id) {
            return Destino::where([
                'canal_venda_id' => $canal_id,
                'slug' => $destino_slug
            ])->first();
        });
    }

    /**
     * Detalhes do destino via slug
     * com as informacoes da home destino
     *
     * @param $destino_slug
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function destinoHomeSlug($destino_slug, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $destino_slug, function () use ($destino_slug, $canal_id) {
            return Destino::with([
                'homeDestino.servicosAtivos' => function($q) {
                    return $q->with('fotoPrincipal', 'categoria')
                        ->select(['servicos.id', 'slug', 'uuid', 'servicos.nome', 'valor_venda', 'cidade']);
                }
            ])->where([
                'canal_venda_id' => $canal_id,
                'slug' => $destino_slug
            ])->first();
        });
    }

    /**
     * Destinos que possuem servicos
     *
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function destinosAtivoSite($cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $canal_id, function () use ($canal_id) {
            return Destino::whereHas('servicosAtivos')->whereHas('homeDestino')->where([
                'canal_venda_id' => $canal_id
            ])->oldest()->get();
        });
    }

    /**
     * Serviços de destaque do destino
     *
     * @param $destino_id
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function destaquesDestino($destino_id, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $destino_id, function () use ($destino_id, $canal_id) {
            return Servico::with([
                'fotoPrincipal', 'categoria'
            ])->where([
                'destino_id' => $destino_id,
                'canal_venda_id' => $canal_id,
                'status' => ServicoEnum::ATIVO
            ])->limit(10)->latest()->get([
                'id', 'slug', 'uuid', 'nome', 'valor_venda', 'cidade'
            ]);
        });
    }

    /**
     * Servicos mais vendidos do destino
     *
     * @param $destino_id
     * @param int $limit
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function servicosMaisVendidos($destino_id, $limit = 10, $cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $canal_id, function () use ($canal_id, $destino_id, $limit) {
            return Servico::select("nome", DB::raw("COUNT(reserva_pedidos.id) AS `vendas`"))
                ->leftJoin('reserva_pedidos', 'reserva_pedidos.servico_id', '=', 'servicos.id')
                ->groupBy('servicos.nome')->where([
                    'canal_venda_id' => $canal_id, 'destino_id' => $destino_id, 'servicos.status' => ServicoEnum::ATIVO
                ])->whereIn('reserva_pedidos.status', [
                    StatusReservaEnum::ATIVA, StatusReservaEnum::FINALIZAR, StatusReservaEnum::UTILIZADO
                ])->orderBy('vendas', 'DESC')->limit($limit)->get();
        });
    }

    /**
     * Recupera o primeiro destino do canal de venda ativo e retorna como default
     *
     * @param bool $cache
     * @return mixed
     * @throws \Exception
     */
    public static function getDestinoDefaultCanal($cache = true)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return self::run($cache, __FUNCTION__ . $canal_id, function () use ($canal_id) {
            return Destino::where('canal_venda_id', $canal_id)->oldest()->first();
        });
    }
}
