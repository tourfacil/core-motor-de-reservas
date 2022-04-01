<?php

namespace TourFacil\Core\Services;

use TourFacil\Core\Enum\Descontos\StatusDesconto;
use TourFacil\Core\Models\CupomDesconto;

abstract class CupomDescontoService
{
    /**
     * Retorna o cupom por código se utilizando das seguintes regras:
     * Somente Cupons ativos, retorna o último cupom cadastrado que:
     * Estiver ativo e estiver dentro do máximo de utilizações
     *
     * @param $codigo
     * @return mixed
     */
    public static function getCupomDescontoByCodigo($codigo) {

        $cupom = CupomDesconto::where('codigo', $codigo)
                              ->whereNull('maximo_utilizacoes')
                              ->orWhereColumn('vezes_utilizado', '<', 'maximo_utilizacoes')
                              ->orderBy('id', 'desc')
                              ->limit(1)
                              ->where('status', '=', StatusDesconto::ATIVO)
                              ->get()
                              ->first();

        return $cupom;
    }

    /**
     * Retorna o cupom por código se utilizando das seguintes regras:
     * Somente Cupons ativos, retorna o último cupom cadastrado que:
     * Estiver ativo e estiver dentro do máximo de utilizações
     * SOMENTE CUPONS QUE NÃO TENHAM UM SERVIÇO ESPECIFICO
     *
     * @param $codigo
     * @return mixed
     */
    public static function getCupomDescontoByCodigoSemServicoEsp($codigo) {

        $cupom = CupomDesconto::where('codigo', $codigo)
            ->whereNull('maximo_utilizacoes')
            ->orWhereColumn('vezes_utilizado', '<', 'maximo_utilizacoes')
            ->orderBy('id', 'desc')
            ->limit(1)
            ->where('status', '=', StatusDesconto::ATIVO)
            ->whereNull('servico_id')
            ->get()
            ->first();

        return $cupom;
    }

    /**
     * Verifica se o serviço o qual o cupom da o desconto esta de fato no carrinho
     *
     * @param $codigo
     * @return void
     */
    public static function isServicoDoCupomNoCarrinho($cupom) {

        // Pega os servicos do carrinho
        $servicos_carrinho = carrinho()->all();

        // Roda todos os serviços do carrinho
        foreach($servicos_carrinho as $servico_carrinho) {

            // Caso ele encontre o servico do cupom no carrinho... Retorna true
            if($servico_carrinho['gtin'] == $cupom->servico_id) {
                return true;
            }
        }

        return false;
    }

    public static function aplicarCupomNaSessao($cupom) {
        session(['cupom_desconto' => $cupom]);
    }

    public static function aplicarCupomNoServico($cupom) {

        // Pega os servicos do carrinho
        $servicos_carrinho = carrinho()->all();
        $servicos_carrinho = $servicos_carrinho->toArray();

        // Roda todos os serviços do carrinho
        foreach($servicos_carrinho as $key => $servico_carrinho) {

            // Caso ele encontre o servico do cupom no carrinho... Retorna true
            if($servico_carrinho['gtin'] == $cupom->servico_id) {
                $servicos_carrinho[$key]['valor_total_cupom'] = "22,90";
            }
        }

        session(['carrinho' => $servicos_carrinho]);

        CupomDescontoService::aplicarCupomNaSessao($cupom);
    }
}
