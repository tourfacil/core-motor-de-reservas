<?php

namespace TourFacil\Core\Services\Pagamento;

use TourFacil\Core\Services\CupomDescontoService;

abstract class DescontoPIXService
{
    /**
     * Retorna se o desconto de PIX esta ativo
     * Config feita direto na .ENV
     * @return bool
     */
    private static function isDescontoPixAtivo() {
        return env('PIX_DESCONTO_ENABLED') === true;
    }

    /**
     * Retorna qual o é o percentual que será descontado
     * Config feita direto na .ENV
     * @return mixed
     */
    public static function getPixDesconto() {
        return env('PIX_DESCONTO_PERCENTUAL');
    }

    /**
     * Retorna o valor ja com o desconto percentual configurado na .ENV
     * Não valida se o desconto esta ou não ativo. Para isso use isDescontoPixAtivo()
     * Caso o valor com desconto seja menor que 0, ele ira arredondar para 1
     * @param $valor
     * @return int|mixed
     */
    public static function calcularValorPixDesconto($valor) {
        return evitarValorMenorQueUm(($valor * (100 - self::getPixDesconto())) / 100);
    }

    /**
     * Retorna se o desconto por PIX pode ser aplicado na situação atual
     * Verifica se o Desconto de PIX esta ativo e se ja não há outro cupom de desconto na sessão
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function isDescontoPIXAplicavel() {

        if(!self::isDescontoPixAtivo()) {
            return false;
        }

        if(CupomDescontoService::isCupomNaSessao()) {
            return false;
        }

        return true;
    }

    /**
     * Retorna o valor que foi descontado.
     * Ex: Se o total for R$ 1.000,00 e haver um desconto 10%. O valor retornado será de R$ 100,00
     * @param $valor
     * @return int|mixed
     */
    public static function calcularValorPixDescontado($valor) {
        return number_format($valor - self::calcularValorPixDesconto($valor), 2);
    }
}
