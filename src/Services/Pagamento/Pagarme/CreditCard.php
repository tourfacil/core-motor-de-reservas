<?php 

namespace TourFacil\Core\Services\Pagamento\Pagarme;

use Exception;
use Illuminate\Http\Request;
use \GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * Class CreditCard
 * @package TourFacil\Core\Services\Pagamento\Getnet\Payment
 */
class CreditCard
{
    /** PATH da URl na API */
    protected $URL = 'https://api.pagar.me/';
    protected $PREFIX = 'core/v5/orders/';

    /**
     * Formatado do array que ira para API
     *
     * @var array
     */
    protected $payload = [
        'code' => '',
        'customer' => [
            'document' => '',
            'phones' => [
                'mobile_phone' => [
                    'country_code' => '',
                    'area_code' => '',
                    'number' => '',
                ],
            ],
            'name' => '',
            'type' => 'individual',
            'email' => '',
            'document_type' => '',
        ],
        'items' => [],
        'payments' => [
            [
                'credit_card' => [
                    'card' => [
                        'billing_address' => [
                            'line_1'   => '',
                            'line_2'   => '',
                            'zip_code' => '',
                            'city'     => '',
                            'state'    => '',
                            'country'  => '',
                        ],
                        'number' => '',
                        'holder_name' => '',
                        'holder_document' => '',
                        'exp_month'   => 0,
                        'exp_year'    => 0,
                        'cvv'         => '',
                    ],
                    'operation_type' => 'auth_and_capture',
                    'installments' =>  1,
                    'statement_descriptor' => 'Tourfacil',
                ],
                'payment_method' => 'credit_card'
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
    public function setItems(Array $items, $juros) {

        $reservas = $items['reservas'];

        foreach($reservas as $reserva) {
            $this->payload['items'][] = [
                'amount'      => $this->toCent($reserva['valor_total']),
                'description' => $reserva['servico'],
                'quantity'    => 1,
                'code'        => $reserva['servico_id'], 
            ];
        }

        if($juros > 0) {
            $this->payload['items'][] = [
                'amount'      => $this->toCent($juros),
                'description' => 'Juros',
                'quantity'    => 1,
                'code'        => 0, 
            ];
        }
    }

    /**
     * Código do pedido
     *
     */
    public function setOrderCode(String $codigo) {
        $this->payload['code'] = '#' . $codigo;
    }

    /**
     * Email do cliente
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
     * CPF do cliente
     *
     */
    public function setCustomerDocument(String $document) {
        $this->payload['customer']['document'] = $this->onlyNumbers($document);
    }

    /**
     * Telefone do cliente
     *
     */
    public function setCustomerPhone(String $phone) {

        $telefone_formatado = $this->onlyNumbers($phone);

        $country_code = '55';
        $area_code = substr($telefone_formatado, 0, 2);

        $telefone_formatado = substr($telefone_formatado, 2, strlen($telefone_formatado));

        $this->payload['customer']['phones']['mobile_phone']['country_code'] = $country_code;
        $this->payload['customer']['phones']['mobile_phone']['area_code'] = $area_code;
        $this->payload['customer']['phones']['mobile_phone']['number'] = $telefone_formatado;
    }

    /**
     * Endereço do cliente
     *
     */
    public function setBillingAdress(String $rua, String $numero, String $bairro, String $cidade, String $estado, String $cep) {

        $billing_address = [ 
            'line_1' => "$numero, $rua, $bairro",
            'line_2' => "$numero, $rua, $bairro, $estado",
            'zip_code' => $this->onlyNumbers($cep),
            'city' => $cidade,
            'state' => $estado,
            'country' => 'BR',
        ];

        $this->payload['payments'][0]['credit_card']['card']['billing_address'] = $billing_address;
    }

    /**
     * Número de parcelas
     *
     * @param $number_installments
     * @return $this
     */
    public function setNumberInstallments(int $number_installments)
    {
        $this->payload['payments'][0]['credit_card']['installments'] = $number_installments;
    }

    /**
     * Numero do cartao
     *
     * @param string $number_card
     * @return $this
     */
    public function setNumberCard(string $number_card)
    {
        $this->payload['payments'][0]['credit_card']['card']['number'] = $this->onlyNumbers($number_card);
    }

    /**
     * Nome impresso no cartão
     *
     * @param string $cardholder_name
     * @return $this
     */
    public function setCardholderName(string $cardholder_name)
    {
        $this->payload['payments'][0]['credit_card']['card']['holder_name'] = strtoupper($this->removeAccentuation($cardholder_name));
    }

    /**
     * Documento do dono do cartão
     *
     */
    public function setCardholderDocument(String $cardholder_document) {
        $this->payload['payments'][0]['credit_card']['card']['holder_document'] = $this->onlyNumbers($cardholder_document);
    }

    /**
     * Código de segurança
     *
     * @param string $security_code
     * @return $this
     */
    public function setSecurityCode(string $security_code)
    {
        $this->payload['payments'][0]['credit_card']['card']['cvv'] = $security_code;
    }

    /**
     * Mês que expira o cartão
     *
     * @param string $expiration_month
     * @return $this
     */
    public function setExpirationMonth(string $expiration_month)
    {
        $this->payload['payments'][0]['credit_card']['card']['exp_month'] = intval($expiration_month);
    }

    /**
     * Ano que expira o cartão
     *
     * @param string $expiration_year
     * @return $this
     */
    public function setExpirationYear(string $expiration_year)
    {
        $this->payload['payments'][0]['credit_card']['card']['exp_year'] = intval($expiration_year);
    }

    /**
     * Efetua cobrança no cartão de crédito
     *
     * @return array
     * @throws \Exception
     */
    public function pay()
    {
        $link = $this->URL . $this->PREFIX;

        $client = new Client();

        $codigo_auth_pagarme = '';

        if(env('APP_ENV') == 'production') {
            $codigo_auth_pagarme = 'Basic c2tfTnhaVkVNMlZ1amg0OU1QWTo=';
        } else {
            $codigo_auth_pagarme = 'Basic c2tfdGVzdF83WExnWkc5SWdobGtWckpROg==';
        }

        try {

            $response = $client->request('POST', $link, [
                'body' => json_encode($this->payload, true),
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => $codigo_auth_pagarme,
                ],
            ]);

            $data = $response->getBody()->getContents();

            $data = json_decode($data, true);

            if($data['status'] == 'paid') {
                return [
                    'approved' => true,
                    'payment_id' => $data['charges'][0]['id'],
                    'response' => $data
                ];
            } else {
                return [
                    'approved' => false,
                    'erro' => $data
                ];
            }

        } catch ( Exception $e) {

            $message = $e->getMessage();

            $erro = "";

            if(strpos($message, "Card expi") != false) {
                $erro = "Cartão vencido - " . $message;
            } else {
                $erro = $message;
            }

            return [
                'approved' => false,
                'erro' => $erro,
            ];
        }
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

    private function toCent(string $valor)
    {
        return (int) number_format($valor * 100, 0, "", "");
    }
}
