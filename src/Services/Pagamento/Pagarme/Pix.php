<?php

namespace TourFacil\Core\Services\Pagamento\Pagarme;

use Exception;
use Illuminate\Http\Request;
use \GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use TourFacil\Core\Enum\StatusPixEnum;
use TourFacil\Core\Models\Pedido;
use Carbon\Carbon;
use TourFacil\Core\Services\Pagamento\DescontoPIXService;

/**
 * Class Pix
 * @package TourFacil\Core\Services\Pagamento\Getnet\Payment
 */
class Pix
{
    /** PATH da URl na API */
    protected static $URL = 'https://api.pagar.me/';
    protected static $PREFIX = 'core/v5/orders/';

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
                'pix' => [
                    'expires_in' => 60,
                    'additional_information' => [
                        [
                            'name'  => 'Tourfacil',
                            'value' => '1',
                        ],
                    ],
                ],
                'payment_method' => 'pix'
            ],
        ],
    ];

    /**
     * Itens que formarão o pedido
     *
     */
    public function setItems(Array $items) {

        $reservas = $items['reservas'];

        foreach($reservas as $reserva) {

            $amount = 0;

            if(DescontoPIXService::isDescontoPIXAplicavel()) {
                $amount = DescontoPIXService::calcularValorPixDesconto($reserva['valor_total']);
            } else {
                $amount = $reserva['valor_total'];
            }

            $this->payload['items'][] = [
                'amount'      => $this->toCent($amount),
                'description' => $reserva['servico'],
                'quantity'    => 1,
                'code'        => $reserva['servico_id'],
            ];
        }
    }

    /**
     * Código do pedido
     *
     */
    public function setOrderCode(String $codigo) {
        $this->payload['code'] = $codigo;
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
     * Tempo de expiração do código PIX. Informar em minutos
     *
     */
    public function setExpiresIn(Int $minutos) {
        $this->payload['payments'][0]['pix']['expires_in'] = $minutos * 60;
    }

    /**
     * Efetua cobrança no cartão de crédito
     *
     * @return array
     * @throws \Exception
     */
    public function gerarCodigoPix()
    {
        $link = self::$URL . self::$PREFIX;

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

            if($data['status'] == 'pending') {

                return [
                    'approved' => true,
                    'payment_id' => $data['charges'][0]['id'],
                    'response' => $data,
                ];

            } else {

                return [
                    'approved' => false,
                    'erro' => 'Erro desconhecido na geração do código PIX',
                ];
            }

        } catch ( Exception $e) {

            $message = $e->getMessage();

            return [
                'approved' => false,
                'erro' => $message,
            ];
        }
    }

    public static function getStatus(Pedido $pedido) {

        $cod_pedido_pagarme = $pedido->transacaoPedido->transacao->transacao->response->id;
        $link = self::$URL . self::$PREFIX . $cod_pedido_pagarme;

        $client = new Client();

        $codigo_auth_pagarme = '';

        if(env('APP_ENV') == 'production') {
            $codigo_auth_pagarme = 'Basic c2tfTnhaVkVNMlZ1amg0OU1QWTo=';
        } else {
            $codigo_auth_pagarme = 'Basic c2tfdGVzdF83WExnWkc5SWdobGtWckpROg==';
        }

        try {

            $response = $client->request('GET', $link, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => $codigo_auth_pagarme,
                ],
            ]);

            $data = $response->getBody()->getContents();

            $data = json_decode($data, true);

            $transacao = $data['charges'][0]['last_transaction'];

            self::isPixExpirado($transacao['expires_at']);

            if($transacao['status'] == 'paid') {
                return StatusPixEnum::PAGO;
            }

            if(self::isPixExpirado($transacao['expires_at'])) {
                return StatusPixEnum::EXPIRADO;
            }

            return StatusPixEnum::PENDENTE;

        } catch ( Exception $e) {

            return StatusPixEnum::PENDENTE;
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

    private static function isPixExpirado($expiracao) {
        $data_expiracao = Carbon::parse($expiracao);
        $agora = Carbon::now();

        if($agora->isAfter($data_expiracao)) {
            return true;
        } else {
            return false;
        }
    }
}
