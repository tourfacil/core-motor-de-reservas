<?php

namespace TourFacil\Core\Services;

use TourFacil\Core\Enum\Descontos\TipoDesconto;
use TourFacil\Core\Enum\Descontos\TipoDescontoValor;

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

                return $valor_original - $valor_desconto;

            // Caso o desconto seja aplciado de forma fixa. Exemplo (Desconto de R$10,00)
            } else if ($desconto->tipo_desconto_valor == TipoDescontoValor::FIXO) {

                $desconto_valor = 0;

                if($net == false) {
                    $desconto_valor = $desconto->desconto;
                } else {
                    $desconto_valor = $desconto->desconto_net;
                }

                // Retorna o novo valor já com o desconto fixo aplicado
                return $valor_original - $desconto_valor;

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

    private static function isDataEntreUtilizacaoValida($desconto, $data)
    {

        $inicio_utilizacao = $desconto->inicio_utilizacao;
        $final_utilizacao = $desconto->final_utilizacao;

        if($data->data->between($inicio_utilizacao, $final_utilizacao)) {
            return true;
        }

        return false;
    }
}
