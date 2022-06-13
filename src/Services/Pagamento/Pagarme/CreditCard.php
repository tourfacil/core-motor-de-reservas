<?php 

namespace TourFacil\Core\Services\Pagamento\Pagarme;

use Illuminate\Http\Request;

/**
 * Class CreditCard
 * @package TourFacil\Core\Services\Pagamento\Getnet\Payment
 */
class CreditCard
{
    /** PATH da URl na API */
    const URL = 'https://api.pagar.me/';
    const PREFIX = 'core/v5/orders/';

    /**
     * Formatado do array que ira para API
     *
     * @var array
     */
    protected $payload = [
        'items' => [],
        'customer' => [
            'name' => 'NOME DO CLIENTE',
            'email' => 'NOME DO E-MAIL'
        ],
        'payments' => [
            'payment_method' => 'credit_card',
            'credit_card' => [
                'recurrence'   => false,
                'installments' =>  1,
                'statement_descriptor' => "Tourfacil",
                'card' => [
                    'number' => '',
                    'holder_name' => '',
                    'exp_month'   => 0,
                    'exp_year'    => 0,
                    'cvv'         => '',
                ],
            ],
        ],
    ];

    /**
     * Valor da compra em centavos
     *
     * @param $valor
     * @return $this
     */
    public function setAmount($valor)
    {
        // Valor da compra
        $this->payload['amount'] = self::toCent($valor);

        return $this;
    }

    /**
     * Itens que formarão o pedido
     *
     */
    public function setItems(Array $items) {

        $reservas = $items['reservas'];

        foreach($reservas as $reserva) {
            $this->payload['items'][] = [
                'amount'      => $reserva['valor_total'],
                'description' => $reserva['servico'],
                'quantity'    => 1
            ];
        }
    }

    /**
     * Nome do cliente
     *
     */
    public function setCustomerEmail(String $customer_email)
    {
        $this->payload['customer']['email'] = $customer_email;
    }

    /**
     * Nome do cliente
     *
     */
    public function setCustomerName(String $customer_name)
    {
        $this->payload['customer']['name'] = $customer_name;
    }

    /**
     * Número de parcelas
     *
     * @param $number_installments
     * @return $this
     */
    public function setNumberInstallments(int $number_installments)
    {
        $this->payload['payments']['credit_card']['installments'] = $number_installments;
    }

    /**
     * Numero do cartao
     *
     * @param string $number_card
     * @return $this
     */
    public function setNumberCard(string $number_card)
    {
        $this->payload['payments']['credit_card']['card']['number'] = $this->onlyNumbers($number_card);
    }

    /**
     * Nome impresso no cartão
     *
     * @param string $cardholder_name
     * @return $this
     */
    public function setCardholderName(string $cardholder_name)
    {
        $this->payload['payments']['credit_card']['card']['holder_name'] = strtoupper($this->removeAccentuation($cardholder_name));
    }

    /**
     * Código de segurança
     *
     * @param string $security_code
     * @return $this
     */
    public function setSecurityCode(string $security_code)
    {
        $this->payload['payments']['credit_card']['card']['cvv'] = $security_code;
    }

    /**
     * Mês que expira o cartão
     *
     * @param string $expiration_month
     * @return $this
     */
    public function setExpirationMonth(string $expiration_month)
    {
        $this->payload['payments']['credit_card']['card']['exp_month'] = $expiration_month;
    }

    /**
     * Ano que expira o cartão
     *
     * @param string $expiration_year
     * @return $this
     */
    public function setExpirationYear(string $expiration_year)
    {
        $this->payload['payments']['credit_card']['card']['exp_year'] = $expiration_year;

        return $this;
    }

    /**
     * Efetua cobrança no cartão de crédito
     *
     * @return array
     * @throws \Exception
     */
    public function pay()
    {


        // Verifica se deu certo
        if($response->status == "APPROVED") {
            return [
                'approved' => true,
                'payment_id' => $response->payment_id,
                'message' => $response->credit->reason_message,
                'response' => $response
            ];
        }

        // Caso falhe a transação
        return [
            'approved' => false,
            'erro' => $response->message ?? "Não foi possível efetuar o pagamento!",
            'response' => $response
        ];
    }

    /**
     * Retorna somente os numeros de uma string
     *
     * @param string $string
     * @return string|string[]|null
     */
    protected function onlyNumbers(string $string)
    {
        return preg_replace("/[^0-9]/", "", $string);
    }

    /**
     * Remove os acentos de uma string
     *
     * @param string $string
     * @return string|string[]|null
     */
    protected function removeAccentuation(string $string)
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
