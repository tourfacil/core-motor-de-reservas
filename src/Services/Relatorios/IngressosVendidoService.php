<?php namespace TourFacil\Core\Services\Relatorios;

use DB;
use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Models\Fornecedor;
use TourFacil\Core\Models\ReservaPedido;

/**
 * Class ReservasAutenticadaService
 * @package TourFacil\Core\Services\Relatorios
 */
class IngressosVendidoService
{
    /**
     * Relatorio de vendas do fornecedor por periodo
     * extra servicos e relacoes
     *
     * @param $fornecedor_id
     * @param $inicio
     * @param $final
     * @param $canal_venda_id
     * @param null $servicos
     * @param array $relacoes
     * @param bool $somente_ativas
     * @return mixed
     */
    public static function relatorioVendidoFornecedor(
        $fornecedor_id, $inicio, $final, $canal_venda_id,
        $servicos = null, $relacoes = [], $somente_ativas = true, $tipo_data = "VENDA"
    )
    {
        $relacoes = array_merge(['servico', 'agendaDataServico'], $relacoes);

        $query = null;

        if($tipo_data == "VENDA") {

            // Pesquisa de ingressos vendidos por venda
            $query = ReservaPedido::with($relacoes)->where('fornecedor_id', $fornecedor_id)
            ->whereHas('pedido', function ($q) use ($canal_venda_id) {
                return $q->where('canal_venda_id', $canal_venda_id);
            })->whereBetween('created_at', [$inicio, $final]);

        } else {

            // Pesquisa de ingressos vendidos por utilização
            $query = ReservaPedido::with($relacoes)->where('fornecedor_id', $fornecedor_id)
            ->whereHas('pedido', function ($q) use ($canal_venda_id) {
                return $q->where('canal_venda_id', $canal_venda_id);
            })->whereHas('agendaDataServico', function($query) use ($inicio, $final) {
                $query->whereBetween('data', [$inicio, $final]);
            });
        }

        // Filtra somente por reservas ativas
        if($somente_ativas) {
            $query->whereIn('status', StatusReservaEnum::RESERVAS_VALIDAS);
        }

        // Filtra por servicos
        if(is_array($servicos)) {
            $query->whereIn('servico_id', $servicos);
        }

        return $query->oldest()->get();
    }

    /**
     * Retorna a lista de fornecedores que tiveram vendas no periodo
     * OU que autenticaram ingressos no periodo
     *
     * @param $inicio
     * @param $final
     * @param $canal_venda_id
     * @param bool $autenticados
     * @return mixed
     */
    public static function relatorioFornecedoresComVendas($inicio, $final, $canal_venda_id, $autenticados = false, $tipo_data = "VENDA")
    {

        // Pesquisa de ingressos vendidos
        $query = Fornecedor::select("fornecedores.id", "nome_fantasia", "fornecedores.cnpj",
            DB::raw(" SUM(reserva_pedidos.valor_total) AS vendido, SUM(reserva_pedidos.valor_net) AS tarifa_net, SUM(reserva_pedidos.quantidade) as quantidade")
        )->leftJoin('reserva_pedidos', 'reserva_pedidos.fornecedor_id', '=', 'fornecedores.id')
         ->leftJoin('servicos', 'servicos.id', '=', 'reserva_pedidos.servico_id')
         ->leftJoin('validacao_reserva_pedidos', 'validacao_reserva_pedidos.reserva_pedido_id', '=', 'reserva_pedidos.id')
         ->leftJoin('agenda_data_servicos', 'agenda_data_servicos.id', '=', 'reserva_pedidos.agenda_data_servico_id')
            ->where('servicos.canal_venda_id', $canal_venda_id)
            ->groupBy('fornecedores.nome_fantasia')->groupBy('fornecedores.id')->groupBy('fornecedores.cnpj');

        // Caso seja para filtrar por reservas autenticadas

        if($tipo_data == "VENDA") {
            if($autenticados == false) {
                $query->whereBetween('reserva_pedidos.created_at', [$inicio, $final])
                    ->whereIn('reserva_pedidos.status', StatusReservaEnum::RESERVAS_VALIDAS);
            } else {
                $query->whereBetween('validacao_reserva_pedidos.created_at', [$inicio, $final])
                    ->where('reserva_pedidos.status', StatusReservaEnum::UTILIZADO);
            }
        } else {
            $query->whereBetween('agenda_data_servicos.data', [$inicio, $final]);
        }

        return $query->orderBy('quantidade', 'DESC')->get();
    }
}
