<?php namespace TourFacil\Core\Services;

use Carbon\Carbon;
use DB;
use TourFacil\Core\Enum\ComissaoStatus;
use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Models\ComissaoTerminal;
use TourFacil\Core\Models\PagamentoTerminal;
use TourFacil\Core\Models\Terminal;

/**
 * Class TerminalService
 * @package TourFacil\Core\Services
 */
class TerminalService
{
    /**
     * Vendas agrupadas por data
     *
     * @param $terminal_id
     * @return mixed
     */
    public static function comissaoAdministrativo($terminal_id)
    {
        $pago = ComissaoStatus::PAGO;
        $pendente = ComissaoStatus::AGUARDANDO;
        $cancelado = ComissaoStatus::CANCELADO;

        return ComissaoTerminal::select("terminal_id", "data_previsao",
            DB::raw("SUM(comissao) as comissao, COUNT(reserva_pedido_id) AS reservas, SUM(IF(comissao_terminais.`status` = '{$pago}', comissao, 0)) AS pago, SUM(IF(comissao_terminais.`status` = '{$pendente}', comissao, 0)) AS pendente, SUM(IF(comissao_terminais.`status` = '$cancelado', comissao, 0)) AS cancelado, SUM(reserva_pedidos.valor_total) AS vendido")
        )->leftJoin('reserva_pedidos', 'reserva_pedidos.id', '=', 'comissao_terminais.reserva_pedido_id')
            ->groupBy('terminal_id')->groupBy('data_previsao')
            ->where('terminal_id', $terminal_id)->orderBy('data_previsao', 'DESC')->get();
    }

    /**
     * Previsão de recebimentos do terminal
     *
     * @param $terminal_id
     * @return mixed
     */
    public static function recebimentosFuturosTerminal($terminal_id)
    {
        $pago = ComissaoStatus::PAGO;
        $pendente = ComissaoStatus::AGUARDANDO;
        $cancelado = ComissaoStatus::CANCELADO;

        return ComissaoTerminal::select("terminal_id", "data_previsao",
            DB::raw("SUM(comissao) as comissao, COUNT(reserva_pedido_id) AS reservas, SUM(IF(comissao_terminais.`status` = '{$pago}', comissao, 0)) AS pago, SUM(IF(comissao_terminais.`status` = '{$pendente}', comissao, 0)) AS pendente, SUM(IF(comissao_terminais.`status` = '$cancelado', comissao, 0)) AS cancelado, SUM(reserva_pedidos.valor_total) AS vendido")
        )->leftJoin('reserva_pedidos', 'reserva_pedidos.id', '=', 'comissao_terminais.reserva_pedido_id')
            ->groupBy('terminal_id')->groupBy('data_previsao')
            ->where('terminal_id', $terminal_id)->where('data_previsao', '>=', today()->startOfMonth())
            ->orderBy('data_previsao')->get();
    }

    /**
     * Dados para o dashboard do terminal
     *
     * @param $terminal
     * @return array
     */
    public static function dashboardTerminal($terminal)
    {
        $vendas_hoje = 0;
        $comissao_hoje = 0;
        $qtd_vendido_30 = 0;
        $qtd_estornado_30 = 0;
        $periodo = periodoPesquisa();
        $hoje_formatado = $periodo['hoje']['start']->format('dmY');

        // Quantidade de comissoes do terminal dos ultimos 30 dias
        $qtd_comissoes = ComissaoTerminal::where('terminal_id', $terminal->id)->whereBetween('created_at', [
            $periodo['ultimos_30']['start'], $periodo['ultimos_30']['end']
        ])->count();

        // Comissao do terminal
        if($qtd_comissoes > 31) {
            $comissoes = ComissaoTerminal::with('reservaPedido.servico')->where('terminal_id', $terminal->id)
                ->whereBetween('created_at', [$periodo['ultimos_30']['start'], $periodo['ultimos_30']['end']])->latest()->get();
        } else {
            $comissoes = ComissaoTerminal::with('reservaPedido.servico')
                ->where('terminal_id', $terminal->id)->limit(31)->latest()->get();
        }

        // Percorre as ultimas comissoes do terminal
        foreach ($comissoes as $comissao) {

            // Data da compra
            $data_compra = $comissao->created_at;

            // Status da reserva ativa
            $reserva_ativa = in_array($comissao->reservaPedido->status, [
                StatusReservaEnum::ATIVA, StatusReservaEnum::FINALIZAR, StatusReservaEnum::UTILIZADO
            ]);

            // Status da reserva cancelada
            $reserva_cancelada = in_array($comissao->reservaPedido->status, [StatusReservaEnum::CANCELADO]);

            // Vendas de hoje
            if($data_compra->format('dmY') == $hoje_formatado) {
                // A reserva deve estar ativa para contabilizar
                if($reserva_ativa) {
                    $vendas_hoje += $comissao->reservaPedido->valor_total;
                    $comissao_hoje += $comissao->comissao;
                }
            }

            // Ultimos 30 dias
            if($periodo['ultimos_30']['start']->diffInDays($data_compra) <= 30) {
                // Quantidade de ingressos vendidos nos ultimos 30 dias
                $qtd_vendido_30 += $comissao->reservaPedido->quantidade;
                // Quantidade de ingressos estornados nos ultimos 30 dias
                if($reserva_cancelada) {
                    $qtd_estornado_30 += $comissao->reservaPedido->quantidade;
                }
            }
        }

        return [
            'vendas_hoje' => $vendas_hoje,
            'comissao_hoje' => $comissao_hoje,
            'qtd_vendido_30' => $qtd_vendido_30,
            'qtd_estornado_30' => $qtd_estornado_30,
            'ultimos_pedidos' => $comissoes
        ];
    }

    /**
     * Lista dos terminais ordenados por vendido e filtrado por periodo
     *
     * @param Carbon $inicio
     * @param Carbon $final
     * @param bool $valor_net
     * @return mixed
     */
    public static function relatorioVendasTerminais(Carbon $inicio, Carbon $final, $valor_net = false)
    {
        $query_net = (!$valor_net) ? "" : ", SUM(reserva_pedidos.valor_net) AS valor_net";

        // vendas dos terminais
        return ComissaoTerminal::with('terminal')->select("terminal_id",
            DB::raw("SUM(comissao) as comissao, SUM(reserva_pedidos.quantidade) as ingressos, SUM(reserva_pedidos.valor_total) AS vendido, SUM(comissao) AS comissao $query_net")
        )->leftJoin('reserva_pedidos', 'reserva_pedidos.id', '=', 'comissao_terminais.reserva_pedido_id')->groupBy('terminal_id')
            ->whereIn('comissao_terminais.status', [ComissaoStatus::AGUARDANDO, ComissaoStatus::PAGO])
            ->whereBetween('comissao_terminais.created_at', [$inicio, $final])->orderBy('vendido', 'DESC')->get();
    }

    /**
     * Valores de vendas do terminal ordenados por vendido e filtrado por periodo
     *
     * @param $terminal_id
     * @param Carbon $inicio
     * @param Carbon $final
     * @param bool $valor_net
     * @return mixed
     */
    public static function valoresVendaTerminal($terminal_id, Carbon $inicio, Carbon $final, $valor_net = false)
    {
        $query_net = (!$valor_net) ? "" : ", SUM(reserva_pedidos.valor_net) AS valor_net";

        // vendas dos terminais
        return ComissaoTerminal::select("terminal_id",
            DB::raw("SUM(comissao) as comissao, SUM(reserva_pedidos.quantidade) as ingressos, SUM(reserva_pedidos.valor_total) AS vendido, SUM(comissao) AS comissao $query_net")
        )->leftJoin('reserva_pedidos', 'reserva_pedidos.id', '=', 'comissao_terminais.reserva_pedido_id')
            ->whereIn('comissao_terminais.status', [ComissaoStatus::AGUARDANDO, ComissaoStatus::PAGO])->groupBy('terminal_id')
            ->whereBetween('comissao_terminais.created_at', [$inicio, $final])->where('terminal_id', $terminal_id)->first();
    }

    /**
     * Relatorio das comissoes a serem pagas aos terminais
     *
     * @param Carbon $inicio
     * @param Carbon $final
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|PagamentoTerminal[]
     */
    public static function relatorioPagamentoDeComissoes(Carbon $inicio, Carbon $final)
    {
        return PagamentoTerminal::with('terminal', 'comissoesPagamento')
            ->whereBetween("mes_pagamento", [$inicio, $final])
            ->orderBy('total_comissao', 'DESC')->get();
    }
}
