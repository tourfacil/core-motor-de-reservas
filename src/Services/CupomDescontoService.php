<?php

namespace TourFacil\Core\Services;

use TourFacil\Core\Enum\Descontos\StatusDesconto;
use TourFacil\Core\Enum\Descontos\TipoDesconto;
use TourFacil\Core\Enum\Descontos\TipoDescontoValor;
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

    /**
     * Aplica o desconto na sessão do cliente
     * Caso o cupom for de serviço especifico, também seta o valor dentro do serviço
     *
     * @param $cupom
     * @return void
     */
    public static function aplicarCupomNaSessao($cupom) {

        // Garante que ja não tenha um outro serviço em promoção devido a cupom
        self::removerCupomServico();

        // Caso o cupom seja de serviço especifico, coloca o novo valor dentro do carrinho
        if($cupom->servico_id != null) {
            self::aplicarCupomNoServico($cupom);
        }

        // Coloca o cupom de desconto na sessão
        session(['cupom_desconto' => $cupom]);
    }

    /**
     * Aplica o desconto dentro do serviço
     *
     * @param $cupom
     * @return void
     */
    private static function aplicarCupomNoServico($cupom) {

        // Pega os servicos do carrinho
        $servicos_carrinho = carrinho()->all();
        $servicos_carrinho = $servicos_carrinho->toArray();

        // Roda todos os serviços do carrinho
        foreach($servicos_carrinho as $key => $servico_carrinho) {

            // Caso ele encontre o servico do cupom no carrinho... Retorna true
            if($servico_carrinho['gtin'] == $cupom->servico_id) {
                $servicos_carrinho[$key]['valor_total_cupom'] = self::aplicarDescontoValor($cupom, $servico_carrinho['valor_total']);
            }
        }

        session(['carrinho' => $servicos_carrinho]);
    }

    /**
     * Remove o cupom de dentro de todos os serviços
     *
     * @return void
     */
    private static function removerCupomServico() {

        // Pega os servicos do carrinho
        $servicos_carrinho = carrinho()->all();
        $servicos_carrinho = $servicos_carrinho->toArray();

        // Roda todos os serviços do carrinho
        foreach($servicos_carrinho as $key => $servico_carrinho) {

            // Remove a variavel valor_total_cupom. Que indica que aquele serviço esta tendo preço reduzido por cupom
            if(array_key_exists('valor_total_cupom', $servico_carrinho)) {
                unset($servicos_carrinho[$key]['valor_total_cupom']);
            }
        }

        session(['carrinho' => $servicos_carrinho]);
    }

    /**
     * Pega um valor e um cupom e retorna o valor ja com o desconto
     * Pode ser usado para ambos os tipos de cupons
     * Não faz alterações no banco de dados, apenas calcula
     * Caso o cupom informado seja nullo, ele retorna o valor original
     * Por segurança, não permite que o valor baixe de RS 1,00
     *
     * @param $cupom
     * @param $valor_original
     * @return int|mixed
     */
    public static function aplicarDescontoValor($cupom, $valor_original) {

        // Caso o cupom seja null. Retorna o valor original
        if($cupom == null) {
            return $valor_original;
        }

        // Caso o cupom for um desconto percentual
        if($cupom->tipo_desconto_valor == TipoDescontoValor::PERCENTUAL) {

            // Retorna o novo valor já com o desconto percentual aplicado
            $valor_desconto =  ($valor_original * $cupom->desconto) / 100;

            // Retorna o valor final
            return self::evitarValorMenorQueUm($valor_original - $valor_desconto);

        // Caso o desconto seja aplciado de forma fixa. Exemplo (Desconto de R$10,00)
        } else if($cupom->tipo_desconto_valor == TipoDescontoValor::FIXO) {

            return self::evitarValorMenorQueUm($valor_original - $cupom->desconto);

        } else {
            // Para evitar BUGS, caso o valor do TipoDescontoValor for inválido... Ele retorna o valor original
            return $valor_original;
        }

    }

    /**
     * Pega um valor NET e um cupom e retorna o valor ja com o desconto
     * Caso o cupom não seja para NET. Irá retornar o valor original
     * Pode ser usado para ambos os tipos de cupons
     * Não faz alterações no banco de dados, apenas calcula
     * Caso o cupom informado seja nullo, ele retorna o valor original
     * Por segurança, não permite que o valor baixe de RS 1,00
     *
     * @param $cupom
     * @param $valor_original
     * @return int|mixed
     */
    public static function aplicarDescontoValorNet($cupom, $valor_original) {

        // Verifica se o cupom aplica no venda ou venda e net
        // Caso aplique no NET ele calcula, se não, retorna o valor original
        if($cupom->tipo_desconto_fornecedor == TipoDesconto::NET) {

            return self::aplicarDescontoValor($cupom, $valor_original);

        } else if($cupom->tipo_desconto_fornecedor == TipoDesconto::VENDA) {
            return $valor_original;
        }
    }

    /**
     * Retorna o valor total do carrinho com o cupom de desconto aplicado
     *
     * @param $valor_sem_cupom
     * @return int|mixed
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function getValorTotalCarrinhoComCupom($valor_sem_cupom) {

        // Recupera o cupom que esta na sessão
        $cupom = session()->get('cupom_desconto');

        // Caso não tenha cupom na sessão, retorna o valor original
        if($cupom == null) {
            return $valor_sem_cupom;
        }

        // Caso o cupom seja para todos os serviços
        if($cupom->servico_id == null) {

            return self::aplicarDescontoValor($cupom, $valor_sem_cupom);
        } else {

            //Busca os serviços do carrinho
            $servicos_carrinho = carrinho()->all();

            // Diferença de valor
            $diferenca = 0;

            // Roda os serviços do carrinho
            foreach($servicos_carrinho as $servico_carrinho) {

                // Caso o desconto existir no serviço
                if(array_key_exists('valor_total_cupom', $servico_carrinho)) {

                    //dd($servico_carrinho);

                    // Vai subtraindo e obtendo a diferença para calcular o valor do cupom
                    $diferenca += $servico_carrinho['valor_total'] - $servico_carrinho['valor_total_cupom'];
                }
            }

            return $valor_sem_cupom - $diferenca;
        }
    }

    /**
     * Remove o cupom da sessão e caso tenha em serviço especifico, remove também
     *
     * @return void
     */
    public static function removerCupomSessao() {

        // Remove o desconto do serviço caso haja
        self::removerCupomServico();

        // Remove o cupom de desconto da sessão caso haja
        session()->forget('cupom_desconto');
    }

    /**
     * Função para garantir que o valor inserido não baixe de R$ 1,00
     *
     * @param $valor
     * @return int|mixed
     */
    private static function evitarValorMenorQueUm($valor) {

        if($valor >= 1) {
            return $valor;
        } else {
            return 1;
        }
    }

    /**
     * Retorna se há um cupom ativo na sessão do cliente atualmente
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function isCupomNaSessao() {

        if(session()->get('cupom_desconto') != null)
            return true;

        return false;
    }
}
