<?php namespace TourFacil\Core\Services\Pagamento;

use Cielo\API30\Ecommerce\CieloEcommerce;
use Cielo\API30\Ecommerce\CreditCard;
use Cielo\API30\Ecommerce\Environment;
use Cielo\API30\Ecommerce\Payment;
use Cielo\API30\Ecommerce\Request\CieloRequestException;
use Cielo\API30\Ecommerce\Sale;
use Cielo\API30\Merchant;
use Exception;
use Log;

/**
 * Class CieloCheckout
 * @package TourFacil\Core\Services\Pagamento
 */
class CieloCheckout
{
    /** @var string Merchant ID */
    private static $merchant_id;

    /** @var string Merchant KEY */
    private static $merchant_key;

    /** @var string Soft Descriptor */
    private static $soft_descriptor;

    /** @var int status code payment success */
    private static $payment_success = 2;

    /**
     * Environment da aplicacao
     *
     * @return Environment
     */
    private static function environment()
    {
        return (env('APP_ENV') == 'local') ?
            Environment::sandbox() : Environment::production();
    }

    /**
     * Recupera as chaves da Cielo
     *
     * @return Merchant
     * @throws Exception
     */
    private static function merchantKeys()
    {
        // Recupera as chaves da cielo no .env
        self::$merchant_id = env('CIELO_MERCHANT_ID');
        self::$merchant_key = env('CIELO_MERCHANT_KEY');
        self::$soft_descriptor = env('CIELO_SOFT_DESCRIPTOR');

        // Caso alguma esteja vazia
        if(is_null(self::$merchant_id) || is_null(self::$merchant_key) || is_null(self::$soft_descriptor)) {
            throw new Exception('As chaves da Cielo não estão configuradas no .env');
        }

        return new Merchant(self::$merchant_id, self::$merchant_key);
    }

    /**
     * Efetua o pagamento usando a Cielo
     *
     * @param $array_pedido
     * @param $cliente
     * @param $dados_pagamento
     * @param $parcelamento
     * @return array
     * @throws Exception
     */
    public static function pay($array_pedido, $cliente, $dados_pagamento, $parcelamento)
    {
        // Configure o ambiente
        $environment = self::environment();

        // Configure seu merchant
        $merchant = self::merchantKeys();

        // Dados do cartao de credito
        $card = CardBinService::getBrandByCardNumber($dados_pagamento['numero_cartao']);

        // Soma o valor do juros ao valor a cobrar
        $total_cobrar = $array_pedido['valor_total'] + $parcelamento['valor_juros'];

        // Instância de Sale informando o ID do pedido na loja
        $sale = new Sale($array_pedido['codigo_pedido']);

        // Instância de Customer informando os dados do cliente
        $sale->customer(self::removeAccentuation($cliente->nome))
            ->setIdentity(self::onlyNumbers($cliente->cpf))
            ->setIdentityType("CPF")
            ->setBirthDate($cliente->nascimento->format('Y-m-d'))
            ->setEmail($cliente->email);

        // Instância de Payment informando o valor do pagamento
        $payment = $sale->payment(self::toCent($total_cobrar))
            ->setInstallments((int) $dados_pagamento['parcelas'])
            ->setSoftDescriptor(self::$soft_descriptor);

        // Instância de Credit Card utilizando os dados do cartão cliente
        $payment->setType(Payment::PAYMENTTYPE_CREDITCARD)->setCapture(true)
            ->creditCard($dados_pagamento['codigo_cartao'], self::getBrandCard($card['brand_enum']))
            ->setExpirationDate("{$dados_pagamento['validade_mes_cartao']}/{$dados_pagamento['validade_ano_cartao']}")
            ->setHolder($dados_pagamento['nome_cartao'])
            ->setCardNumber($card['number']);

        // Cria o pagamento na Cielo
        try {
            // log da transacao
            $logger = (env('APP_ENV') == 'local') ? Log::driver() : null;

            // Configura o SDK com o merchant e o ambiente apropriado para criar a venda
            $sale = (new CieloEcommerce($merchant, $environment, $logger))->createSale($sale);

            // Com a venda criada na Cielo, já temos o ID do pagamento, TID
            $payment_response = $sale->getPayment();

            // Response da API em array
            $response = array_filter($payment_response->jsonSerialize(), function ($item) {
                return (! is_null($item));
            });

            // Verifica o status code da venda
            if($payment_response->getStatus() == self::$payment_success) {
                return [
                    'approved' => true,
                    'payment_id' => $payment_response->getPaymentId(),
                    'message' => $payment_response->getReturnMessage(),
                    'response' => $response
                ];
            }

            // Caso a venda nao seja autorizada
            return [
                'approved' => false,
                'erro' => $payment_response->getReturnMessage(),
                'response' => $response
            ];

        } catch (CieloRequestException $e) {

            // Em caso de erros de integração, podemos tratar o erro aqui.
            // os códigos de erro estão todos disponíveis no manual de integração.
            $error = $e->getCieloError();

            // Caso a venda nao seja autorizada
            return [
                'approved' => false,
                'erro' => (is_object($error)) ? $error->getMessage() : "Erro transacional",
                'response' => (is_object($error)) ? $e->getCode() : "Erro transacional"
            ];
        }
    }

    /**
     * Cancela uma transacao
     *
     * @param $payment_id
     * @param $amount
     * @throws CieloRequestException
     * @throws Exception
     */
    public static function cancel($payment_id, $amount)
    {
        // Configure o ambiente
        $environment = self::environment();

        // Configure seu merchant
        $merchant = self::merchantKeys();

        // Efetua o cancelamento
        $sale = (new CieloEcommerce($merchant, $environment))->cancelSale($payment_id, $amount);

        dd($sale);
    }

    /**
     * Retorna a bandeira do cartao de acordo com a API
     *
     * @param $brand
     * @return mixed
     */
    private static function getBrandCard($brand)
    {
        $cards = [
            CardBinService::VISA => CreditCard::VISA,
            CardBinService::MASTERCARD => CreditCard::MASTERCARD,
            CardBinService::AMERICAN_EXPRESS => CreditCard::AMEX,
            CardBinService::ELO => CreditCard::ELO,
            CardBinService::JCB => CreditCard::JCB,
            CardBinService::DINERS_CLUB => CreditCard::DINERS,
            CardBinService::DISCOVER => CreditCard::DISCOVER,
            CardBinService::HIPERCARD => CreditCard::HIPERCARD,
        ];

        return $cards[$brand];
    }

    /**
     * Transforma real para centavo
     *
     * @param string $valor
     * @return int
     */
    private static function toCent(string $valor)
    {
        return (int) number_format($valor * 100, 0, "", "");
    }

    /**
     * Retorna somente os numeros de uma string
     *
     * @param string $string
     * @return string|string[]|null
     */
    private static function onlyNumbers(string $string)
    {
        return preg_replace("/[^0-9]/", "", $string);
    }

    /**
     * Remove os acentos de uma string
     *
     * @param string $string
     * @return string|string[]|null
     */
    private static function removeAccentuation(string $string)
    {
        $string = preg_replace('/[áàãâä]/ui', 'a', $string);
        $string = preg_replace('/[éèêë]/ui', 'e', $string);
        $string = preg_replace('/[íìîï]/ui', 'i', $string);
        $string = preg_replace('/[óòõôö]/ui', 'o', $string);
        $string = preg_replace('/[úùûü]/ui', 'u', $string);
        $string = preg_replace('/[ç]/ui', 'c', $string);

        return $string;
    }
}
