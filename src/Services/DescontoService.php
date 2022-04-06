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
    public static function aplicarDescontoValor($desconto, $valor_original) {

        // Caso não tenha desconto ativo ele retorna o mesmo valor
        if($desconto == null) {
            return $valor_original;
        } else {

            // Caso o desconto seja aplicado de forma percentual
            if($desconto->tipo_desconto_valor == TipoDescontoValor::PERCENTUAL) {

                // Retorna o novo valor já com o desconto percentual aplicado
                $valor_desconto =  ($valor_original * $desconto->desconto) / 100;

                return $valor_original - $valor_desconto;

            // Caso o desconto seja aplciado de forma fixa. Exemplo (Desconto de R$10,00)
            } else if ($desconto->tipo_desconto_valor == TipoDescontoValor::FIXO) {

                // Retorna o novo valor já com o desconto fixo aplicado
                return $valor_original - $desconto->desconto;

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
    public static function aplicarDescontoValorNet($desconto, $valor) {

        // Caso não tenha desconto ativo ele retorna o mesmo valor
        if($desconto == null) {
            return $valor;
        }

        // Caso o desconto seja também para o fornecedor ele calcula o desconto e retorna
        if($desconto->tipo_desconto_fornecedor == TipoDesconto::NET) {

            return self::aplicarDescontoValor($desconto, $valor);

        // Caso o desconto seja somente no venda ele retorna o net original
        } else if($desconto->tipo_desconto_fornecedor == TipoDesconto::VENDA) {

            return $valor;
        }
    }
}
