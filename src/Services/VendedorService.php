<?php

namespace TourFacil\Core\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Models\ReservaPedido;
use TourFacil\Core\Models\Vendedor;

abstract class VendedorService
{
    /**
     * Calcula a comissão do afiliado
     * @param ReservaPedido $reserva
     * @return float|int
     */
    public static function getComissaoVendedor(ReservaPedido $reserva) {

        if($reserva->vendedor_id == null) {
            return 0;
        }

        $vendedor = $reserva->vendedor;

        switch ($reserva->servico->categorias->first()->nome) {

            case 'Passeios':
                return self::regraTres($reserva->valor_total, $vendedor->comissao_passeios);

            case 'Ingressos':
                return self::regraTres($reserva->valor_total, $vendedor->comissao_ingressos);

            case 'Gastronomia':
                return self::regraTres($reserva->valor_total, $vendedor->comissao_gastronomia);

            case 'Transfer':
                return self::regraTres($reserva->valor_total, $vendedor->comissao_transfer);

            default:
                return self::regraTres($reserva->valor_total, $vendedor->comissao_ingressos);
        }
    }

    // /**
    //  * Retorna qual o percentual o afiliado ganhará na reserva
    //  * @param ReservaPedido $reserva
    //  * @return int
    //  */
    public static function getComissaoPercentual(ReservaPedido $reserva) {

        if($reserva->vendedor_id == null) {
            return 0;
        }

        $vendedor = $reserva->vendedor;

        switch ($reserva->servico->categorias->first()->nome) {

            case 'Passeios':
               return $vendedor->comissao_passeios;

            case 'Gastronomia':
                return $vendedor->comissao_gastronomia;

            case 'Transfer':
                return $vendedor->comissao_transfer;

            default:
                return $vendedor->comissao_ingressos;
        }
    }

    /**
     * Faz a regra de três para saber o valor que o afiliado ganhará
     * @param $valor
     * @param $porcentagem
     * @return float|int
     */
    private static function regraTres($valor, $porcentagem) {

        $porcentagem = str_replace(",", '.', $porcentagem);
        return ($valor * $porcentagem) / 100;
    }

    /**
     * Faz os cálculos e retorna os dados necessários para o relatório de afiliados geral
     * @param Request $request
     * @return array
     */
    public static function relatorioVendedores(Request $request) {

        // Array para guardar os dados necessários
        $dados = [];

        // Guarda o tipo de data vindo da requisição e caso não seja informado, seta como VENDA
        $dados['tipo_data'] = $request->get('tipo_operacao') ?? 'VENDA';

        if(is_null($request->get('inicio')) || is_null($request->get('final'))) {
            $dados['inicio'] = Carbon::now()->startOfMonth();
            $dados['final'] = Carbon::now()->endOfMonth();
        } else {
            $dados['inicio'] = Carbon::parse($request->get('inicio'));
            $dados['final'] = Carbon::parse($request->get('final'));
        }

        // Inicia a query buscando reservas não nullas e onde o status esteja válido
        $reservas = ReservaPedido::whereNotNull('vendedor_id')
            ->whereIn('status', StatusReservaEnum::RESERVAS_VALIDAS);

        // Busca as reservas por VENDA ou UTILIZACAO
        if($dados['tipo_data'] == 'UTILIZACAO') {
            $reservas->whereHas('agendaDataServico', function($query) use ($dados) {
                $query->whereDate('data', '>=', $dados['inicio']);
                $query->whereDate('data', '<=', $dados['final']);
            });
        } else {
            $reservas->whereDate('created_at', '>=', $dados['inicio']);
            $reservas->whereDate('created_at', '<=', $dados['final']);
        }

        // Carrega as relações de agenda e vendedore
        $reservas = $reservas->with([
            'agendaDataServico',
            'vendedor'
        ]);

        // Busca as reservas
        $reservas = $reservas->get();

        $dados['total_vendido'] = 0;
        $dados['total_comissionado'] = 0;
        $dados['total_quantidade'] = 0;
        $dados['vendedores'] = [];

        foreach($reservas as $reserva) {
            $comissao_vendedor = VendedorService::getComissaoVendedor($reserva);

            $dados['total_vendido'] += $reserva->valor_total;
            $dados['total_comissionado'] += $comissao_vendedor;
            $dados['total_quantidade'] += $reserva->quantidade;

            if(!array_key_exists($reserva->vendedor->nome_fantasia, $dados['vendedores'])) {
                $dados['vendedores'][$reserva->vendedor->nome_fantasia]['valor_venda'] = $reserva->valor_total;
                $dados['vendedores'][$reserva->vendedor->nome_fantasia]['valor_comissao'] = $comissao_vendedor;
                $dados['vendedores'][$reserva->vendedor->nome_fantasia]['quantidade'] = $reserva->quantidade;
                $dados['vendedores'][$reserva->vendedor->nome_fantasia]['vendedor'] = $reserva->vendedor;
            } else {
                $dados['vendedores'][$reserva->vendedor->nome_fantasia]['valor_venda'] += $reserva->valor_total;
                $dados['vendedores'][$reserva->vendedor->nome_fantasia]['valor_comissao'] += $comissao_vendedor;
                $dados['vendedores'][$reserva->vendedor->nome_fantasia]['quantidade'] += $reserva->quantidade;
            }
        }

        // Transforma o array de vendedores em Collection para organizar por valor de venda
        $dados['vendedores'] = collect($dados['vendedores']);
        $dados['vendedores'] = $dados['vendedores']->sortByDesc('valor_venda');

        return $dados;
    }

    /**
     * Faz os cálculos e retorna os dados necessários para o relatório de afiliados especifico
     * @param Request $request
     * @return array
     */
    public static function relatorioVendedor(Request $request) {

        // Recupera informações da URL
        $vendedor_id = $request->get('vendedor_id');
        $data_inicio = $request->get('inicio');
        $data_final = $request->get('final');
        // O tipo de operação é para decidir se o relatório será por data de venda ou utilização
        $tipo_operacao = $request->get('tipo_operacao');

        $vendedor = Vendedor::find($vendedor_id);

        // Transforma as datas para o modelo Carbon
        $data_inicio = \Carbon\Carbon::parse($data_inicio);
        $data_final =  \Carbon\Carbon::parse($data_final);

        // Busca as reservas do vendedor pela data da venda ou utilização

        if($tipo_operacao == "UTILIZACAO") {

            // Busca todas as reservas do vendedor pelo periodo de utilização
            $reservas = ReservaPedido::where('vendedor_id', $vendedor->id)
                ->whereHas('agendaDataServico', function($query) use ($data_inicio, $data_final) {
                    $query->whereDate('data', '>=', $data_inicio);
                    $query->whereDate('data', '<=', $data_final);
                })
                ->whereIn('status', StatusReservaEnum::RESERVAS_VALIDAS)
                ->with(['servico', 'servico.categorias'])
                ->get();

        } else if($tipo_operacao == "VENDA") {

            // Busca todas as reservas do vendedor pelo periodo de venda
            $reservas = ReservaPedido::where('vendedor_id', $vendedor->id)
                ->whereHas('agendaDataServico')
                ->whereDate('created_at', '>=', $data_inicio)
                ->whereDate('created_at', '<=', $data_final)
                ->whereIn('status', StatusReservaEnum::RESERVAS_VALIDAS)
                ->with(['servico', 'servico.categorias'])
                ->get();
        }

        // Monta o ARRAY com todos os dados necessários para imprimir a página
        $dados = [
            'vendedor' => $vendedor,
            'data_inicio' => $data_inicio,
            'data_final' => $data_final,
            'reservas' => $reservas,
            'tipo_operacao' => $tipo_operacao,
            'total_comissionado' => 0,
            'total_vendido' => 0,
            'quantidade_reservas' => 0,
        ];

        foreach($reservas as $reserva) {
            $dados['total_comissionado'] += VendedorService::getComissaoVendedor($reserva);
            $dados['total_vendido'] += $reserva->valor_total;
            $dados['quantidade_reservas']++;
        }

        return $dados;
    }
}
