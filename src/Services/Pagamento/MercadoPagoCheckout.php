<?php namespace TourFacil\Core\Services\Pagamento;

use Exception;
use MercadoPago\Item;
use MercadoPago\Payment;
use MercadoPago\SDK as SdkMercadoPago;

/**
 * Class MercadoPagoCheckout
 * @package TourFacil\Core\Services\Pagamento
 */
class MercadoPagoCheckout
{
    /**
     * Efetua o pagamento via Mercado pago
     *
     * @param $array_pedido
     * @param $cliente
     * @param $dados_pagamento
     * @param $parcelamento
     * @param $response_mp
     * @return array
     */
    public static function pay($array_pedido, $cliente, $dados_pagamento, $parcelamento, $response_mp)
    {
        try {

            // Soma o valor do juros ao valor a cobrar
            $total_cobrar = $array_pedido['valor_total'] + $parcelamento['valor_juros'];

            // Token do Mercado pago
            SdkMercadoPago::setAccessToken(env('MC_ACCESS_TOKEN'));

            // Configura o pagamento via Mercado pago
            $payment = new Payment();
            $payment->transaction_amount = $total_cobrar;
            $payment->token = $response_mp['id'];
            $payment->binary_mode = true;
            $payment->description = env('MC_SOFT_DESCRIPTOR') . " Pedido #" . $array_pedido['codigo_pedido'];
            $payment->installments = (int) $dados_pagamento['parcelas'];
            $payment->payment_method_id = $dados_pagamento['bandeira_cartao'];
            //$payment->notification_url = "https://enynzzgjdc1o.x.pipedream.net";

            // Nome do comprador
            $nome_array = explode(" ", self::removeAccentuation($cliente->nome));
            $nome = $nome_array[0] ?? self::removeAccentuation($cliente->nome);
            if(isset($nome_array[1])) unset($nome_array[0]);
            $sobrenome = implode(" ", $nome_array);

            // Dados do comprador
            $payment->payer = [
                "email" => $cliente->email,
                "first_name" => $nome,
                "last_name" => $sobrenome,
                "identification" => [
                    "type" => "CPF",
                    "number" => self::onlyNumbers($cliente->cpf)
                ]
            ];

            // Coloca os itens adquiridos
            $items = [];
            foreach ($array_pedido['reservas'] as $reserva) {
                $data = explode("/", $reserva['data_utilizacao']);
                $item = new Item();
                $item->id = $reserva['servico_id'];
                $item->title = $reserva['servico'];
                $item->quantity = $reserva['quantidade'];
                $item->unit_price = number_format($reserva['valor_total'] / $reserva['quantidade'], 2);
                $item->picture_url = $reserva['foto_principal'];
                $item->event_date = "{$data[2]}-{$data[1]}-{$data[0]}"; // Data do evento para anti fraude
                $items[] = $item;
            }
            $payment->additional_info = ['items' => $items];

            // Save and posting the payment
            $status = $payment->save();

            // Verifica se deu certo
            if($payment->status == "approved" && $status) {
                return [
                    'approved' => true,
                    'payment_id' => $payment->id,
                    'message' => $payment->status_detail,
                    'response' => $payment->toArray()
                ];
            }

            // Caso falhe a transação
            return [
                'approved' => false,
                'erro' => $payment->message ?? "Não foi possível efetuar o pagamento!",
                'response' => $payment->toArray()
            ];

        } catch (Exception $e) {
            return [
                'approved' => false,
                'erro' => (is_object($e)) ? $e->getMessage() : "Erro transacional",
                'response' => (is_object($e)) ? $e->getCode() : "Erro transacional"
            ];
        }
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
