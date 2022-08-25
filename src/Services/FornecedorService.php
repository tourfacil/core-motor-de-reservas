<?php namespace TourFacil\Core\Services;

use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Models\ReservaPedido;
use TourFacil\Core\Models\Servico;

/**
 * Class FornecedorService
 * @package PortalGramado\Admin\Services
 */
class FornecedorService
{
    /**
     * Dashboard fornecedor
     *
     * @param $fornecedor_id
     * @return array
     */
    public static function dashboardFornecedor($fornecedor_id)
    {
        $vendas_hoje = 0;
        $vendas_mes = 0;
        $qtd_vendido_30 = 0;
        $qtd_estornado_30 = 0;
        $periodo = periodoPesquisa();
        $hoje_formatado = $periodo['hoje']['start']->format('dmY');

        // Ultimas reservas do fornecedor
        $reservas = ReservaPedido::with([
            'agendaDataServico' => function($q) {
                return $q->select(['id', 'data']);
            },
            'servico' => function($q) {
                return $q->select(['id', 'nome']);
            }
        ])->whereIn('status', [StatusReservaEnum::ATIVA, StatusReservaEnum::CANCELADO, StatusReservaEnum::UTILIZADO])
        ->where('fornecedor_id', $fornecedor_id)->latest()->get();

        // Percorre as ultimas vendas do fornecedor
        foreach ($reservas as $reserva) {

            // Status da reserva ativa
            $reserva_ativa = in_array($reserva->status, StatusReservaEnum::RESERVAS_VALIDAS);

            // O status da reserva deve estar ativo para contabilizar
            if($reserva->created_at->format('dmY') == $hoje_formatado && $reserva_ativa) {
                $vendas_hoje += $reserva->valor_net;
            }

            // Vendido no mes deve estar ativo para contabilizar
            if(($periodo['este_mes']['start']->month == $reserva->created_at->month) && $reserva_ativa) {
                $vendas_mes += $reserva->valor_net;
            }

            // Ultimos 30 dias
            if($periodo['ultimos_30']['start']->diffInDays($reserva->created_at) <= 30) {
                // Quantidade de ingressos vendidos nos ultimos 30 dias
                $qtd_vendido_30 += $reserva->quantidade;
                // Quantidade de ingressos estornados nos ultimos 30 dias
                if($reserva->status == StatusReservaEnum::CANCELADO) {
                    $qtd_estornado_30 += $reserva->quantidade;
                }
            }
        }

        return [
            'vendas_hoje' => $vendas_hoje,
            'vendas_mes' => $vendas_mes,
            'qtd_vendido_30' => $qtd_vendido_30,
            'qtd_estornado_30' => $qtd_estornado_30,
            'ultimas_vendas' => $reservas
        ];
    }

    /**
     * Reservas do fornecedor
     *
     * @param $fornecedor
     * @param $pp_start
     * @param $pp_end
     * @return mixed
     */
    public static function reservasFornecedor($fornecedor, $pp_start, $pp_end)
    {
        return ReservaPedido::with([
            'agendaDataServico' => function($q) {
                return $q->select(['id', 'data']);
            },
            'pedido' => function($q) {
                return $q->select(['id', 'codigo', 'cliente_id'])
                    ->with(['cliente' => function($f) { return $f->select(['id', 'nome', 'email']);}]);
            },
            'servico' => function($q) {
                return $q->select(['id', 'nome']);
            }
        ])->where('fornecedor_id', $fornecedor)
            ->whereIn('status', [StatusReservaEnum::ATIVA, StatusReservaEnum::CANCELADO, StatusReservaEnum::UTILIZADO])
            ->whereBetween('created_at', [$pp_start, $pp_end])->latest()->get();
    }

    /**
     * Reservas do fornecedor
     *
     * @param $fornecedor
     * @param $pp_start
     * @param $pp_end
     * @return mixed
     */
    public static function reservasAtivasFornecedor($fornecedor)
    {
        return ReservaPedido::with([
            'agendaDataServico' => function($q) {
                return $q->select(['id', 'data']);
            },
            'pedido' => function($q) {
                return $q->select(['id', 'codigo', 'cliente_id'])
                    ->with(['cliente' => function($f) {
                        return $f->select(['id', 'nome', 'email', 'cpf']);
                    }]);
            },
            'servico' => function($q) {
                return $q->select(['id', 'nome']);
            }
        ])->where([
            'fornecedor_id' => $fornecedor,
            'status' => StatusReservaEnum::ATIVA
        ])->latest()->get();
    }

    /**
     * Retorna os servicos do parceiro
     *
     * @param $fornecedor_id
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Servico[]
     */
    public static function getServicosFornecedor($fornecedor_id)
    {
        return Servico::with('canalVenda', 'destino')
            ->where('fornecedor_id', $fornecedor_id)->orderBy('nome')->get([
                'id', 'canal_venda_id', 'destino_id',  'nome', 'valor_venda', 'status'
        ]);
    }

    /**
     * Procura uma reserva do fornecedor
     *
     * @param $voucher
     * @param $fornecedor_id
     * @param int $limit
     * @return mixed
     */
    public static function searchReservaFornecedor($voucher, $fornecedor_id, $limit = 10)
    {
        return ReservaPedido::with([
            'servico' => function($q) {
                return $q->select(['id', 'nome']);
            },
        ])->whereLike(['voucher'], $voucher)->where('fornecedor_id', $fornecedor_id)
            ->latest()->limit($limit)->get(['id', 'servico_id', 'voucher', 'quantidade']);
    }

    /**
     * Reservas autenticadas do fornecedor filtrado por data e servicos
     *
     * @param $fornecedor_id
     * @param $inicio
     * @param $final
     * @param null $servicos
     * @param array $relacoes
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|ReservaPedido[]
     */
    public static function reservasAutenticadasFornecedor($fornecedor_id, $inicio, $final, $servicos = null, $relacoes = [])
    {
        $relacoes = array_merge(['servico', 'agendaDataServico', 'validacao'], $relacoes);

        // Pesquisa das reservas autenticadas
        $query = ReservaPedido::with($relacoes)
            ->whereHas('validacao', function ($q) use ($inicio, $final) {
                return $q->whereBetween('validado', [$inicio, $final]);
            })->where([
                'fornecedor_id' => $fornecedor_id,
                'status' => StatusReservaEnum::UTILIZADO
            ]);

        // Filtra por servicos
        if(is_array($servicos)) {
            $query->whereIn('servico_id', $servicos);
        }

        return $query->get();
    }

    /**
     * Reservas vendidas pro fornecedor por data de venda
     *
     * @param $fornecedor_id
     * @param $inicio
     * @param $final
     * @param null $servicos
     * @param array $relacoes
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|ReservaPedido[]
     */
    public static function reservasVendidasFornecedor($fornecedor_id, $inicio, $final, $servicos = null, $relacoes = [])
    {
        $relacoes = array_merge(['servico', 'agendaDataServico'], $relacoes);

        // Pesquisa de ingressos vendidos
        $query = ReservaPedido::with($relacoes)->where('fornecedor_id', $fornecedor_id)
            ->whereBetween('created_at', [$inicio, $final]);

        // Filtra por servicos
        if(is_array($servicos)) {
            $query->whereIn('servico_id', $servicos);
        }

        return $query->oldest()->get();
    }

    /**
     * Reservas vendidas pro fornecedor por data de utilização
     *
     * @param $fornecedor_id
     * @param $inicio
     * @param $final
     * @param null $servicos
     * @param array $relacoes
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|ReservaPedido[]
     */
    public static function reservasVendidasFornecedorUtilizacao($fornecedor_id, $inicio, $final, $servicos = null, $relacoes = [])
    {
        $relacoes = array_merge(['servico', 'agendaDataServico'], $relacoes);

        // Pesquisa de ingressos vendidos
        $query = ReservaPedido::with($relacoes)->where('fornecedor_id', $fornecedor_id)
            ->whereHas('agendaDataServico', function($query) use ($inicio, $final) {
                $query->whereBetween('data', [$inicio, $final]);
            })->whereIn('status', [StatusReservaEnum::UTILIZADO, StatusReservaEnum::ATIVA, StatusReservaEnum::CANCELADO]);

        // Filtra por servicos
        if(is_array($servicos)) {
            $query->whereIn('servico_id', $servicos);
        }

        return $query->oldest()->get();
    }
}
