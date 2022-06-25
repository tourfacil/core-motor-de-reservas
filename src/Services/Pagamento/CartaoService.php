<?php

namespace TourFacil\Core\Services\Pagamento;

use TourFacil\Core\Enum\MetodoPagamentoEnum;
use TourFacil\Core\Enum\CartaoCreditoLivreEnum;
use TourFacil\Core\Services\Pagamento\Pagarme\PagarmeCheckout;

/**
 * Class CartaoService
 * @package TourFacil\Core\Services\Pagamento
 */
class CartaoService
{
    /**
     * Realiza a cobranca no cartao de credito usando a Getnet
     *
     * @param $array_pedido
     * @param $cliente
     * @param $dados_pagamento
     * @param $parcelamento
     * @return array
     */
    public static function payCreditCardGetnet($array_pedido, $cliente, $dados_pagamento, $parcelamento)
    {
        // API Ecommerce da Cielo
        $payment = GetnetCheckout::pay($array_pedido, $cliente, $dados_pagamento, $parcelamento);

        // Caso seja aprovado
        if ($payment['approved']) {
            return [
                "approved" => true,
                "payment_id" => $payment['payment_id'],
                "dados_pagamento" => [
                    'gateway' => MetodoPagamentoEnum::GETNET,
                    'nome_cartao' => $dados_pagamento["nome_cartao"],
                    'numero_cartao' => maskNumberCard($dados_pagamento["numero_cartao"]),
                    'parcelas' => $dados_pagamento["parcelas"],
                    'bandeira' => $dados_pagamento["bandeira_cartao"],
                    'transacao' => $payment
                ],
            ];
        }

        return [
            "approved" => false,
            "message" => $payment['erro'],
            "dados_pagamento" => [
                'gateway' => MetodoPagamentoEnum::GETNET,
                'nome_cartao' => $dados_pagamento["nome_cartao"],
                'numero_cartao' => maskNumberCard($dados_pagamento["numero_cartao"]),
                'parcelas' => $dados_pagamento["parcelas"],
                'bandeira' => $dados_pagamento["bandeira_cartao"],
                'transacao' => $payment
            ],
        ];
    }

    /**
     * Efetua o pagamento em cartao de credito usando a API do Mercado Pago
     *
     * @param $array_pedido
     * @param $cliente
     * @param $dados_pagamento
     * @param $parcelamento
     * @param $response_mp
     * @return array
     */
    public static function payCreditCardMercadoPago($array_pedido, $cliente, $dados_pagamento, $parcelamento, $response_mp)
    {
        // API Mercado Pago
        $payment = MercadoPagoCheckout::pay($array_pedido, $cliente, $dados_pagamento, $parcelamento, $response_mp);

        // Caso seja aprovado
        if ($payment['approved']) {
            return [
                "approved" => true,
                "payment_id" => $payment['payment_id'],
                "dados_pagamento" => [
                    'gateway' => MetodoPagamentoEnum::MERCADO_PAGO,
                    'nome_cartao' => $payment['response']['card']->cardholder->name,
                    'numero_cartao' => $payment['response']['card']->first_six_digits . "****" . $payment['response']['card']->last_four_digits,
                    'parcelas' => $payment['response']["installments"],
                    'bandeira' => $dados_pagamento["bandeira_cartao"],
                    'transacao' => $payment
                ],
            ];
        }

        return [
            "approved" => false,
            "message" => $payment['erro'],
            "dados_pagamento" => [
                'gateway' => MetodoPagamentoEnum::MERCADO_PAGO,
                'nome_cartao' => $response_mp['cardholder']['name'],
                'numero_cartao' => $response_mp['first_six_digits'] . "****" . $response_mp['last_four_digits'],
                'parcelas' => $dados_pagamento["parcelas"],
                'bandeira' => $dados_pagamento["bandeira_cartao"],
                'transacao' => $payment
            ],
        ];
    }

    /**
     * Realiza a cobranca no cartao de credito usando a cielo
     *
     * @param $array_pedido
     * @param $cliente
     * @param $dados_pagamento
     * @param $parcelamento
     * @return array
     * @throws \Exception
     */
    public static function payCreditCardCielo($array_pedido, $cliente, $dados_pagamento, $parcelamento)
    {
        //Inicializa variavel
        $payment = [];

        // Verifica se cartão selecionado é de geração de pedido manual livre
        if ($dados_pagamento['numero_cartao'] != CartaoCreditoLivreEnum::NUMERO_CARTAO_LIVRE) {
            // API Ecommerce da Cielo
            $payment = CieloCheckout::pay($array_pedido, $cliente, $dados_pagamento, $parcelamento);
        } else {
            $payment['approved'] = true;
            $payment['payment_id'] = uniqid();
        }

        // Caso seja aprovado
        if ($payment['approved']) {
            return [
                "approved" => true,
                "payment_id" => $payment['payment_id'],
                "dados_pagamento" => [
                    'gateway' => MetodoPagamentoEnum::CIELO,
                    'nome_cartao' => $dados_pagamento["nome_cartao"],
                    'numero_cartao' => maskNumberCard($dados_pagamento["numero_cartao"]),
                    'parcelas' => $dados_pagamento["parcelas"],
                    'bandeira' => $dados_pagamento["bandeira_cartao"],
                    'transacao' => $payment
                ],
            ];
        }

        return [
            "approved" => false,
            "message" => $payment['erro'],
            "dados_pagamento" => [
                'gateway' => MetodoPagamentoEnum::CIELO,
                'nome_cartao' => $dados_pagamento["nome_cartao"],
                'numero_cartao' => maskNumberCard($dados_pagamento["numero_cartao"]),
                'parcelas' => $dados_pagamento["parcelas"],
                'bandeira' => $dados_pagamento["bandeira_cartao"],
                'transacao' => $payment
            ],
        ];
    }

    /**
     * Efetua o pagamento em cartao de credito usando a API da Wirecard
     *
     * @param $array_pedido
     * @param $cliente
     * @param $dados_pagamento
     * @param $parcelamento
     * @param $hash_wirecard
     * @return array
     */
    public static function payCreditCardWireCard($array_pedido, $cliente, $dados_pagamento, $parcelamento, $hash_wirecard)
    {
        // API WireCard
        $payment = WireCardCheckout::pay($array_pedido, $cliente, $dados_pagamento, $parcelamento, $hash_wirecard);

        // Caso seja aprovado
        if ($payment['approved']) {

            return [
                "approved" => true,
                "payment_id" => $payment['payment_id'],
                "dados_pagamento" => [
                    'gateway' => MetodoPagamentoEnum::WIRECARD,
                    'nome_cartao' => $dados_pagamento["nome_cartao"],
                    'numero_cartao' => maskNumberCard($dados_pagamento["numero_cartao"]),
                    'parcelas' => $dados_pagamento["parcelas"],
                    'bandeira' => $dados_pagamento["bandeira_cartao"],
                    'transacao' => $payment,
                ],
            ];
        }

        return [
            "approved" => false,
            "message" => $payment['erro'],
            "dados_pagamento" => [
                'gateway' => MetodoPagamentoEnum::WIRECARD,
                'nome_cartao' => $dados_pagamento["nome_cartao"],
                'numero_cartao' => maskNumberCard($dados_pagamento["numero_cartao"]),
                'parcelas' => $dados_pagamento["parcelas"],
                'bandeira' => $dados_pagamento["bandeira_cartao"],
                'transacao' => $payment
            ],
        ];
    }

    /**
     * Realiza a cobranca no cartao de credito usando a Pagarme
     *
     * @param $array_pedido
     * @param $cliente
     * @param $dados_pagamento
     * @param $parcelamento
     * @return array
     */
    public static function payCreditCardPagarme($array_pedido, $cliente, $dados_pagamento, $parcelamento)
    {
        // API Ecommerce da Cielo
        $payment = PagarmeCheckout::pay($array_pedido, $cliente, $dados_pagamento, $parcelamento);

        // Caso seja aprovado
        if ($payment['approved']) {
            return [
                "approved" => true,
                "payment_id" => $payment['payment_id'],
                "dados_pagamento" => [
                    'gateway' => MetodoPagamentoEnum::PAGARME,
                    'nome_cartao' => $dados_pagamento["nome_cartao"],
                    'numero_cartao' => maskNumberCard($dados_pagamento["numero_cartao"]),
                    'parcelas' => $dados_pagamento["parcelas"],
                    'bandeira' => $dados_pagamento["bandeira_cartao"],
                    'transacao' => $payment
                ],
            ];
        }

        return [
            "approved" => false,
            "message" => $payment['erro'],
            "dados_pagamento" => [
                'gateway' => MetodoPagamentoEnum::PAGARME,
                'nome_cartao' => $dados_pagamento["nome_cartao"],
                'numero_cartao' => maskNumberCard($dados_pagamento["numero_cartao"]),
                'parcelas' => $dados_pagamento["parcelas"],
                'bandeira' => $dados_pagamento["bandeira_cartao"],
                'transacao' => $payment
            ],
        ];
    }
}
