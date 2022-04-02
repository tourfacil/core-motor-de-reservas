<?php

namespace TourFacil\Core\Services;

use TourFacil\Core\Enum\Descontos\StatusDesconto;
use TourFacil\Core\Models\CupomDesconto;

abstract class CupomDescontoService
{
    /**
     * Retorna um cupom válido baseado nas seguintes regras:
     * - Será o último cupom cadastrado com o $codigo informado
     * - Será exibido se o status estiver como ´ATIVO´
     * - Será exibido caso o número máximo de utilizações seja menor que o número de vezes utilizado
     * - Ou Se o máximo_utilizações for igual a NULL, ou seja infinito
     * - Caso seja um cupom para servico especifico, só será retornado caso o servico estiver de fato no carrinho
     * - Será retornado somente um cupom e caso não tenha nenhum previsto com essas regras, será retornado NULL
     *
     * @param $codigo
     * @return mixed
     */
    public static function getCupomValidoByCodigo($codigo) {

        // Busca o ID de todos os serviços que estão no carrinho
        $ids_servicos_carrinho = self::getIDSProdutosCarrinho();

        // Query que busca todos os cupons se usando das regras descritas na PHPDoc deste método
        $cupom = CupomDesconto::where('codigo', $codigo)
            ->where('status', StatusDesconto::ATIVO)
            ->where(function($query) use ($ids_servicos_carrinho) {
                $query->whereNull('servico_id');
                $query->orWhereIn('servico_id', $ids_servicos_carrinho);
            })
            ->where(function($query) {
                $query->whereNull('maximo_utilizacoes');
                $query->orWhereColumn('maximo_utilizacoes', '>', 'vezes_utilizado');
            })
            ->orderBy('id', 'Desc')
            ->limit(1)
            ->get()
            ->first();

        // Retorna o cupom
        return $cupom;
    }

    /**
     * Roda os serviços que estão no carrinho do cliente e retorna todos os ids dos mesmos
     *
     * @return array
     */
    private static function getIDSProdutosCarrinho() {

        // Pega os servicos do carrinho
        $servicos_carrinho = carrinho()->all();

        // Variavel para guadar os ids
        $ids_servicos = [];

        // Roda todos os servicos do carrinho e guarda os ids
        foreach($servicos_carrinho as $servico_carrinho) {
            $ids_servicos[] = $servico_carrinho['gtin'];
        }

        // Retorna o array de IDs
        return $ids_servicos;
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
