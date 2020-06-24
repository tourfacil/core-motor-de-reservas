<?php namespace TourFacil\Core\Services\Pagamento;

use Exception;
use Moip\Auth\BasicAuth;
use Moip\Moip;
use Moip\Resource\Orders;

/**
 * Class WireCardCheckout
 * @package TourFacil\Core\Services\Pagamento
 */
class WireCardCheckout
{
    /**
     * @var string
     */
    private static $token;

    /**
     * @var string
     */
    private static $key;

    /**
     * @var string
     */
    private static $soft_descriptor;

    /**
     * Endpoint de acordo com o ambiente da aplicacao
     *
     * @return string
     */
    private static function endpoint()
    {
        return (env('APP_ENV') == 'local')
            ? Moip::ENDPOINT_SANDBOX : Moip::ENDPOINT_PRODUCTION;
    }

    /**
     * Cria uma instacao da classe Moip
     *
     * @return Moip
     * @throws Exception
     */
    private static function moip()
    {
        // Recupera as chaves da Wirecard do .env
        self::$token = env('WIRECARD_TOKEN');
        self::$key = env('WIRECARD_KEY');
        self::$soft_descriptor = env('WIRECARD_DESCRIPTOR');

        // Caso alguma esteja vazia
        if(is_null(self::$token) || is_null(self::$key) || is_null(self::$soft_descriptor)) {
            throw new Exception('As chaves da WireCard não estão configuradas no .env');
        }

        return new Moip(new BasicAuth(self::$token, self::$key), self::endpoint());
    }

    /**
     * Efetua o pagamento usando a Wirecard
     *
     * @param $array_pedido
     * @param $cliente
     * @param $dados_pagamento
     * @param $parcelamento
     * @param $hash_wirecard
     * @return array
     * @throws Exception
     */
    public static function pay($array_pedido, $cliente, $dados_pagamento, $parcelamento, $hash_wirecard)
    {
        // Recupera uma instacia da Moip
        $moip = self::moip();

        // Recupera o telefone do cliente
        $telefone = self::onlyNumbers($cliente->telefone);
        $dd_telefone = (int) substr($telefone, 0, 2);
        $numero_telefone = (int) substr($telefone, 2, 11);

        // Dados do comprador
        $customer = $moip->customers()
            ->setOwnId($cliente->uuid)
            ->setFullname($cliente->nome)
            ->setEmail($cliente->email)
            ->setBirthDate($cliente->nascimento)
            ->setTaxDocument(self::onlyNumbers($cliente->cpf))
            ->setPhone($dd_telefone, $numero_telefone);

        // Dados da compra
        $order = $moip->orders()->setOwnId($array_pedido['codigo_pedido']);

        // Soma o valor do juros ao valor a cobrar
        $total_cobrar = $array_pedido['valor_total'] + $parcelamento['valor_juros'];

        // Adiciona um item com o valor total da compra
        $order->addItem("Pedido #". $array_pedido['codigo_pedido'] . " " . self::$soft_descriptor,1, $array_pedido['codigo_pedido'], self::toCent($total_cobrar));

        // Coloca o dados do comprador ao pedido
        $order->setCustomer($customer);

        // Adiciona o split de pagamento
        foreach ($array_pedido['split'] as $item_split) {
            /**
             * Adiciona o parceiro para split de pagamento
             * Parametros
             * 1° Token do parceiro (Wirecard)
             * 2° Recebedor tipo secundario (não pode fazer alteracoes no pagamento)
             * 3° Valor em centavos a receber na conta
             */
            $order->addReceiver($item_split['token'], Orders::RECEIVER_TYPE_SECONDARY, self::toCent($item_split['valor_net']));
        }

        try {

            // Cria o pedido na Wirecard
            $order = $order->create();

            // Verifica se criou o pedido
            if($order->getStatus() == "CREATED") {

                // Pagador MESMO QUE O COMPRADOR
                $holder = $moip->holders()->setFullname($cliente->nome)
                    ->setBirthDate($cliente->nascimento)
                    ->setTaxDocument(self::onlyNumbers($cliente->cpf), 'CPF')
                    ->setPhone($dd_telefone, $numero_telefone);

                // Pagamento
                $payment = $order->payments()
                    ->setCreditCardHash($hash_wirecard, $holder, false)
                    ->setInstallmentCount((int) $dados_pagamento['parcelas'])
                    ->setStatementDescriptor(self::$soft_descriptor);

                // Efetua a cobranca com a Wirecard
                $response = $payment->execute();

                dd($response, $array_pedido);
                /**
                 * VERIFICAR O RETORNO
                 */

                return [
                    'approved' => true,
                    'payment_id' => $response->getId(),
                    'message' => $response->getStatus(),
                    'response' => $response
                ];

            } else {
                return [
                    'approved' => false,
                    'erro' => 'Falha ao criar o pedido na Wirecard',
                    'response' => $order
                ];
            }

        } catch (Exception $e) {
            return [
                'approved' => false,
                'erro' => $e->getMessage() ?? "Erro ao criar o pedido",
                'response' => $e->getCode() ?? "Falha ao criar o pedido"
            ];
        }
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
