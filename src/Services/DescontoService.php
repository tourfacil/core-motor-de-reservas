<?php

namespace TourFacil\Core\Services;

use Carbon\Carbon;
use TourFacil\Core\Enum\Descontos\StatusDesconto;
use TourFacil\Core\Enum\Descontos\TipoDesconto;
use TourFacil\Core\Enum\Descontos\TipoDescontoValor;
use TourFacil\Core\Models\AgendaDataServico;
use TourFacil\Core\Models\Servico;

abstract class DescontoService
{
    /**
     * Retorna o valor do serviço já com o desconto aplicado
     * Caso não tenha desconto ativo ele retorna o valor original
     *
     * @param $desconto
     * @param $valor_original
     * @return float|int|mixed
     */
    public static function aplicarDescontoValor($desconto, $valor_original, $data, $net = false) {

        // Caso não tenha desconto ativo ele retorna o mesmo valor
        if($desconto == null || self::isDataEntreUtilizacaoValida($desconto, $data) == false) {

            return $valor_original;

        } else {

            // Caso o desconto seja aplicado de forma percentual
            if($desconto->tipo_desconto_valor == TipoDescontoValor::PERCENTUAL) {

                $desconto_valor = 0;

                if($net == false) {
                    $desconto_valor = $desconto->desconto;
                } else {
                    $desconto_valor = $desconto->desconto_net;
                }

                // Retorna o novo valor já com o desconto percentual aplicado
                $valor_desconto =  ($valor_original * $desconto_valor) / 100;

                $valor_final = $valor_original - $valor_desconto;

                if($valor_final < 0) {
                    $valor_final = 0;
                }

                return $valor_final;

            // Caso o desconto seja aplciado de forma fixa. Exemplo (Desconto de R$10,00)
            } else if ($desconto->tipo_desconto_valor == TipoDescontoValor::FIXO) {

                $desconto_valor = 0;

                if($net == false) {
                    $desconto_valor = $desconto->desconto;
                } else {
                    $desconto_valor = $desconto->desconto_net;
                }

                $valor_final = $valor_original - $desconto_valor;

                if($valor_final < 0) {
                    $valor_final = 0;
                }

                // Retorna o novo valor já com o desconto fixo aplicado
                return $valor_final;

            } else {
                // Para evitar BUGS, caso o valor do TipoDescontoValor for inválido... Ele retorna o valor original
                return $valor_original;
            }
        }
    }

    /**
     * @param $desconto
     * @param $valor
     * @return float|int|mixed|void
     */
    public static function aplicarDescontoValorNet($desconto, $valor, $data) {

        // Caso não tenha desconto ativo ele retorna o mesmo valor
        if($desconto == null) {
            return $valor;
        }

        // Caso o desconto seja também para o fornecedor ele calcula o desconto e retorna
        if($desconto->tipo_desconto_fornecedor == TipoDesconto::NET) {

            return self::aplicarDescontoValor($desconto, $valor, $data, true);

        // Caso o desconto seja somente no venda ele retorna o net original
        } else if($desconto->tipo_desconto_fornecedor == TipoDesconto::VENDA) {

            return $valor;
        }
    }

    public static function isDataEntreUtilizacaoValida($desconto, $data)
    {

        $inicio_utilizacao = $desconto->inicio_utilizacao;
        $final_utilizacao = $desconto->final_utilizacao;

        if($data->data->between($inicio_utilizacao, $final_utilizacao)) {
            return true;
        }

        return false;
    }

    public static function isDataEntreVendaValida($desconto, $data)
    {

        $inicio = $desconto->inicio;
        $final = $desconto->final;

        if($data->data->between($inicio, $final)) {
            return true;
        }

        return false;
    }

    public static function getServicosComDescontoCarrinho()
    {
        $carrinho = carrinho()->all();

        if($carrinho->count() == 0) {
            return false;
        }

        $servicos_com_desconto = [];

        foreach($carrinho as $servico_carrinho) {

            $servico = Servico::find($servico_carrinho['gtin']);

            $desconto = $servico->descontoAtivo;

            if($desconto == null) {
                continue;
            }

            $agenda = AgendaDataServico::find($servico_carrinho['agenda_selecionada']['data_servico_id']);

            if(DescontoService::isDataEntreUtilizacaoValida($desconto, $agenda)){
                $servicos_com_desconto[] = [
                    'id' => $servico->id,
                    'nome' => $servico->nome,
                ];
            }
        }

        if(count($servicos_com_desconto) == 0) {
            return false;
        }

        return $servicos_com_desconto;
    }

    public static function getStatusAtual($desconto)
    {
        if($desconto->status == StatusDesconto::INATIVO) {
            return [
                'texto' => 'Inativo',
                'cor' => 'danger',
            ];
        }

        $data = new AgendaDataServico();
        $data->data = Carbon::now();

        if(self::isDataEntreUtilizacaoValida($desconto, $data) && self::isDataEntreVendaValida($desconto, $data)) {
            return [
                'texto' => 'Ativo agora',
                'cor' => 'success',
            ];
        }

        if($data->data->isAfter($desconto->final)) {
            return [
                'texto' => 'Expirado',
                'cor' => 'info',
            ];
        }

        return [
            'texto' => 'Agendado',
            'cor' => 'warning',
        ];
    }
}
