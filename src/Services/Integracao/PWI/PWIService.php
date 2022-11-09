<?php

namespace TourFacil\Core\Services\Integracao\PWI;

use Carbon\Carbon;
use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Models\IntegracaoPWI;
use TourFacil\Core\Models\ReservaPedido;

abstract class PWIService
{
    public static function integrarReserva(ReservaPedido  $reserva) {

        $api = new PWIAPI();

        $variacoes = self::getVariacoesResolvidas($reserva);

        $dados = [
            'TerminalVenda' => 'Tour Facil',
            'Usuario' => 'Tour Facil',
            'IdVendaOrigem' => $reserva->id,
            'DataHoraVenda' => $reserva->created_at->toDateTimeLocalString(),
            'Itens' => [],
        ];

        foreach($variacoes as $variacao) {
            $dados['Itens'][] = [
                'IdProduto' => $variacao['variacao_pwi_id'],
                'DataPrevisaoVisita' => $reserva->agendaDataServico->data->toDateTimeLocalString(),
                'Qtde' => $variacao['quantidade'],
            ];
        }

        $retorno = $api->efetuarCompra($dados);

        if($retorno['error'] == true) {
            return false;
        }

        sleep(3);

        $retorno_delay = $api->consultarVenda($retorno['data']['id']);

        IntegracaoPWI::create([
            'reserva_pedido_id' => $reserva->id,
            'integracao' => $reserva->servico->integracao,
            'status' => StatusReservaEnum::ATIVA    ,
            'dados' => json_encode($retorno_delay),
            'data_utilizacao' => $reserva->agendaDataServico->data
        ]);

        return true;
    }

    public static function getNumerosPassaporte(ReservaPedido $reserva) {

        $integracao = $reserva->integracaoPWI;

        if($integracao == null) {
            return false;
        }

        $dados = $integracao->dados;

        if(is_string($integracao->dados)) {
            $dados = json_decode($dados, true);
        }

        $itens = $dados['data']['itens'];

        $retorno = [];
        $tag_config = 'integracao.' . strtolower($reserva->servico->integracao) . '.variacoes';

        foreach($itens as $item) {

            foreach($item['ingressos'] as $ingresso) {
                $retorno[] = [
                    'categoria' => array_search($item['idProduto'], config($tag_config)),
                    'digitoPassaporte' => $ingresso['digitoPassaporte'],
                    'passaporte' => $ingresso['numeroPassaporte'],
                ];
            }
        }

        return $retorno;
    }


    public static function getReservasIntegradasMes() {

        $api = new PWIAPI();

        $inicio = Carbon::today()->subDays(1)->startOfMonth()->toDateTimeLocalString();
        $final = Carbon::today()->subDays(1)->toDateTimeLocalString();

        $response = $api->consultarVendas($inicio, $final);
        dd($response);

    }

    private static function getVariacoesResolvidas(ReservaPedido $reserva) {

        $configuracao = config('integracao.skyglass.variacoes');
        $variacoes_reserva = $reserva->quantidadeReserva;

        $retorno = [];

        foreach($variacoes_reserva as $variacao_reserva) {

            $variacao_servico = $variacao_reserva->variacaoServico;

            $retorno[] = [
                'variacao' => $variacao_servico->nome,
                'quantidade' => $variacao_reserva->quantidade,
                'variacao_pwi_id' => $configuracao[$variacao_servico->nome]
            ];
        }

        return $retorno;
    }
}
