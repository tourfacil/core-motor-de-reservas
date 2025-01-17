<?php 

namespace TourFacil\Core\Services\Pagamento\Pagarme;

use TourFacil\Core\Models\Cliente;
use TourFacil\Core\Services\Pagamento\Pagarme\CreditCard;

class PagarmeCheckout
{
    /**
     * Pagamento no cartão de crédito utilizando o pagarme
     *
     * @param $array_pedido
     * @param $cliente
     * @param $dados_pagamento
     * @param $parcelamento
     * @return array
     */
    public static function pay($array_pedido, $cliente, $dados_pagamento, $parcelamento) {
        
        try {

            // Soma o valor do juros ao valor a cobrar
            $total_cobrar = $array_pedido['valor_total'] + $parcelamento['valor_juros'];

            // Validade do cartao de credito
            $validade_ano = $dados_pagamento['validade_ano_cartao'];

            if(strlen($validade_ano) > 2) {
                $validade_ano = $validade_ano[2] . $validade_ano[3];
            }

            // Nova instancia do CreditCard Getnet
            $sale = new CreditCard();

            // Informa os dados do cliente (comprador)
            $sale->setCustomerName($cliente->nome);
            $sale->setCustomerEmail($cliente->email);
            $sale->setCustomerDocument($cliente->cpf);
            $sale->setCustomerPhone($cliente->telefone);
            
            // Informa o código do pedido
            $sale->setOrderCode($array_pedido['codigo_pedido']);

            $cliente = Cliente::where('id', $cliente->id)->with(['endereco'])->get()->first();

            // Informa os dados do endereço do comprador
            $sale->setBillingAdress(
                $cliente->endereco->rua,
                $cliente->endereco->numero,
                $cliente->endereco->bairro,
                $cliente->endereco->cidade,
                $cliente->endereco->estado,
                $cliente->endereco->cep
            );            

            // Informa os dados do cartão e número de parcelas
            $sale->setNumberInstallments((int) $dados_pagamento['parcelas']);
            $sale->setNumberCard($dados_pagamento['numero_cartao']);
            $sale->setExpirationMonth($dados_pagamento['validade_mes_cartao']);
            $sale->setExpirationYear($validade_ano);
            $sale->setCardholderName($dados_pagamento['nome_cartao']);
            $sale->setCardholderDocument($cliente->cpf);
            $sale->setSecurityCode($dados_pagamento['codigo_cartao']);

            // Informa os serviços sendo adquiridos
            $sale->setItems($array_pedido, $parcelamento['valor_juros']);

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
}
