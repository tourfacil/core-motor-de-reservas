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

        $fornecedores = [];

        // Caso o tipo de data for igual a venda será puxado um relatório com as seguintes caracteristicas.
        // 1 - Todos os fornecedores com reservas vendidas entre as datas $inicio e $final, com as reservas do mesmo periodo.
        // 2 - Se $autenticados for verdadeiro, somente retornará as reservas que foram marcadas como utilizado pelo fornecedor, se não, todas.
        // 3 - Só buscara as reservas do canal de venda informado.
        if($tipo_data == 'VENDA') {

            $fornecedores = Fornecedor::whereHas('reservas', function($query) use ($inicio, $final, $canal_venda_id, $autenticados) {
                $query->whereBetween('created_at', [$inicio, $final])
                      ->whereIn('status', ($autenticados ? [StatusReservaEnum::UTILIZADO] : StatusReservaEnum::RESERVAS_VALIDAS))
                      ->whereHas('servico', function($query2) use ($canal_venda_id) {
                            $query2->where('canal_venda_id', $canal_venda_id);
                        });
                })                        
            ->with(['reservas' => function($query) use ($inicio, $final, $canal_venda_id, $autenticados) {
                $query->whereBetween('created_at', [$inicio, $final])
                      ->whereIn('status', ($autenticados ? [StatusReservaEnum::UTILIZADO] : StatusReservaEnum::RESERVAS_VALIDAS))
                      ->whereHas('servico', function($query2) use ($canal_venda_id) {
                            $query2->where('canal_venda_id', $canal_venda_id);
                        });
                }])
            ->select('id', 'nome_fantasia', 'cnpj')
            ->get();

        } else {

        // Caso o tipo de data for igual a utilização será puxado um relatório com as seguintes caracteristicas.
        // 1 - Todos os fornecedores com reservas com utilização entre as datas $inicio e $final, com as reservas do mesmo periodo.
        // 2 - Se $autenticados for verdadeiro, somente retornará as reservas que foram marcadas como utilizado pelo fornecedor, se não, todas.
        // 3 - Só buscara as reservas do canal de venda informado.

            $fornecedores = Fornecedor::whereHas('reservas', function($query) use ($inicio, $final, $canal_venda_id, $autenticados) {
                $query->whereHas('agendaDataServico', function($query2) use ($inicio, $final) {
                    $query2->whereBetween('data', [$inicio, $final]);
                })
                ->whereIn('status', ($autenticados ? [StatusReservaEnum::UTILIZADO] : StatusReservaEnum::RESERVAS_VALIDAS))
                ->whereHas('servico', function($query2) use ($canal_venda_id) {
                    $query2->where('canal_venda_id', $canal_venda_id);
                });
            })
            ->with(['reservas' => function($query) use ($inicio, $final, $canal_venda_id, $autenticados) {
                $query->whereHas('agendaDataServico', function($query2) use ($inicio, $final) {
                    $query2->whereBetween('data', [$inicio, $final]);
                })
                ->whereIn('status', ($autenticados ? [StatusReservaEnum::UTILIZADO] : StatusReservaEnum::RESERVAS_VALIDAS))
                ->whereHas('servico', function($query2) use ($canal_venda_id) {
                    $query2->where('canal_venda_id', $canal_venda_id);
                });
            }])
            ->select('id', 'nome_fantasia', 'cnpj')
            ->get();
        }

        // Percorre todos os fornecedores retornados
        foreach($fornecedores as $fornecedor) {
            
            $vendido = 0;
            $tarifa_net = 0;
            $quantidade = 0;

            // Percorre todas as reservas do fornecedores para contar de forma precisa todos os valores
            foreach($fornecedor->reservas as $reserva) {

                $vendido += $reserva->valor_total;
                $tarifa_net += $reserva->valor_net;
                $quantidade += $reserva->quantidade;
            }

            // Aumenta o contador de acordo com o vendido pelo fornecedor
            $fornecedor->vendido = $vendido;
            $fornecedor->tarifa_net = $tarifa_net;
            $fornecedor->quantidade = $quantidade;
        }

        // Retorna a lista com os valores de quantidades, net e venda ordenados de maior para menor por venda
        return $fornecedores->sortByDesc('vendido');
    }
}
