<?php namespace TourFacil\Core\Services;

use Cache;
use TourFacil\Core\Enum\StatusPedidoEnum;
use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Models\Afiliado;
use TourFacil\Core\Models\CanalVenda;
use TourFacil\Core\Models\ComissaoTerminal;
use TourFacil\Core\Models\Pedido;

/**
 * Class CanalVendaService
 * @package TourFacil\Core\Services
 */
class CanalVendaService
{
    /**
     * Prefixo do cache para canais de venda
     *
     * @var string
     */
    private static $prefix_cache = "canal_venda_";

    /**
     * Tag default para o cache do canal de venda
     *
     * @var string
     */
    private static $tag = "canalVenda";

    /**
     * Canais de vendas ativos
     *
     * @param bool $cache
     * @return mixed
     */
    public static function getCanaisDeVendaAtivos($cache = true)
    {
        if($cache) {
            return Cache::tags(self::$tag)->remember(self::$prefix_cache . __FUNCTION__, 60, function () {
                return CanalVenda::orderBy('created_at', 'DESC')->get();
            });
        }

        return CanalVenda::orderBy('created_at', 'DESC')->get();
    }

    /**
     * Dados para o dashboard
     *
     * @param $canal_venda
     * @return array
     */
    public static function dashboardCanal($canal_venda, Afiliado $afiliado = null)
    {
        $vendas_hoje = 0;
        $vendas_mes = 0;
        $qtd_vendido_30 = 0;
        $qtd_estornado_30 = 0;
        $periodo = periodoPesquisa();
        $hoje_formatado = $periodo['hoje']['start']->format('dmY');

        // Quantidade de vendas do canal nos ultimos 30 dias
        $qtd_vendas = Pedido::where('canal_venda_id', $canal_venda->id)
            ->whereBetween('created_at', [
                $periodo['ultimos_30']['start'], $periodo['ultimos_30']['end']
            ]);
            if($afiliado) {
                $qtd_vendas->whereHas('reservas', function($query) use ($afiliado) {
                    $query->where('afiliado_id', $afiliado->id);
                });
            }
            $qtd_vendas = $qtd_vendas->get()->count();

        // Vendas do canal
        if($qtd_vendas > 31) {
            $vendas = Pedido::with([
                'reservas.servico' => function($q) {
                    return $q->select('id', 'nome');
                }
            ])->where('canal_venda_id', $canal_venda->id)->whereBetween('created_at', [
                $periodo['ultimos_30']['start'], $periodo['ultimos_30']['end']
            ])->latest();
        } else {
            $vendas = Pedido::with([
                'reservas.servico' => function($q) {
                    return $q->select('id', 'nome');
                }
            ])->where('canal_venda_id', $canal_venda->id)->limit(31)->latest();
        }

        if($afiliado) {
            $vendas->whereHas('reservas', function($query) use ($afiliado) {
                $query->where('afiliado_id', $afiliado->id);
            });
        }

        $vendas = $vendas->get();

        // Percorre as ultimas vendas do canal
        foreach ($vendas as $pedido) {

            // Data da compra
            $data_compra = $pedido->created_at;

            // Quantidade total nas reservas do pedido
            $quantidade_pedido = $pedido->reservas->sum('quantidade');

            // Status do pedido ativo
            $pedido_ativo = in_array($pedido->status, [
                StatusPedidoEnum::UTILIZADO, StatusPedidoEnum::PAGO,
            ]);

            // Status do pedido cancelado
            $pedido_cancelado = in_array($pedido->status, [
                StatusPedidoEnum::CANCELADO, StatusPedidoEnum::EXPIRADO
            ]);

            // O pedido deve estar ativo para contabilizar
            if($data_compra->format('dmY') == $hoje_formatado && $pedido_ativo) {
                $vendas_hoje += $pedido->valor_total + $pedido->juros;
            }

            // Vendido no mes deve estar ativo para contabilizar
            if(($periodo['este_mes']['start']->month == $data_compra->month) && $pedido_ativo) {
                $vendas_mes += $pedido->valor_total + $pedido->juros;
            }

            // Ultimos 30 dias
            if($periodo['ultimos_30']['start']->diffInDays($data_compra) <= 30) {
                // Quantidade de ingressos vendidos nos ultimos 30 dias
                $qtd_vendido_30 += $quantidade_pedido;
                // Quantidade de ingressos estornados nos ultimos 30 dias
                if($pedido_cancelado) {
                    $qtd_estornado_30 += $quantidade_pedido;
                }
            }
        }

        return [
            'vendas_hoje' => $vendas_hoje,
            'vendas_mes' => $vendas_mes,
            'qtd_vendido_30' => $qtd_vendido_30,
            'qtd_estornado_30' => $qtd_estornado_30,
            'ultimas_vendas' => $vendas
        ];

    }

    /**
     * Atualiza o cache dos canais de venda
     *
     * @return bool
     */
    public static function flushCache()
    {
        return Cache::tags(self::$tag)->flush();
    }
}
