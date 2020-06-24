<?php namespace TourFacil\Core\Services\Pagamento;

use Exception;
use TourFacil\Core\Services\Pagamento\Getnet\Payment\CreditCard;

/**
 * Class GetnetCheckout
 * @package TourFacil\Core\Services\Pagamento
 */
class GetnetCheckout
{
    /**
     * Pagamento no cartão de crédito utilizando a Getnet
     *
     * @param $array_pedido
     * @param $cliente
     * @param $dados_pagamento
     * @param $parcelamento
     * @return array
     */
    public static function pay($array_pedido, $cliente, $dados_pagamento, $parcelamento)
    {
        try {

            // Soma o valor do juros ao valor a cobrar
            $total_cobrar = $array_pedido['valor_total'] + $parcelamento['valor_juros'];

            // Validade do cartao de credito
            $validade_ano = $dados_pagamento['validade_ano_cartao'];

            // Nova instancia do CreditCard Getnet
            $sale = new CreditCard();

            // Informa o numero do pedido
            $sale->setOrderId($array_pedido['codigo_pedido']);

            // Informa os dados do cliente (comprador)
            $sale->setCustomerName($cliente->nome)
                ->setCustomerId($cliente->id)
                ->setCustomerDocumentNumber($cliente->cpf)
                ->setCustomerEmail($cliente->email);

            // Instância de Payment informando o valor do pagamento
            $sale->setAmount($total_cobrar)
                ->setNumberInstallments((int) $dados_pagamento['parcelas'])
                ->setSoftDescriptor(self::getSoftDescriptor());

            // Instância de Credit Card utilizando os dados do cartão cliente
            $sale->setNumberCard($dados_pagamento['numero_cartao'])
                ->setExpirationMonth($dados_pagamento['validade_mes_cartao'])
                ->setExpirationYear($validade_ano[2] . $validade_ano[3])
                ->setCardholderName($dados_pagamento['nome_cartao'])
                ->setSecurityCode($dados_pagamento['codigo_cartao']);

            // Efetua a cobrança no cartão
            return $sale->pay();

        } catch (Exception $e) {
            return [
                'approved' => false,
                'erro' => (is_object($e)) ? $e->getMessage() : "Erro transacional",
                'response' => (is_object($e)) ? $e->getCode() : "Erro transacional"
            ];
        }
    }

    /**
     * Recupera o soft descriptor do .env
     *
     * @return mixed
     * @throws Exception
     */
    private static function getSoftDescriptor()
    {
        // Recupera o soft descriptor do env
        $soft_descriptor = env('GETNET_SOFT_DESCRIPTOR');

        // Caso esteja vazio
        if(is_null($soft_descriptor)) {
            throw new Exception('O soft descriptor não está configurado no .env');
        }

        return $soft_descriptor;
    }
}
