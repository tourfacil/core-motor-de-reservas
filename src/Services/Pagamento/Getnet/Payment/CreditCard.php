<?php namespace TourFacil\Core\Services\Pagamento\Getnet\Payment;

use Illuminate\Http\Request;
use TourFacil\Core\Services\Pagamento\Getnet\Service\RequestConnect;
use TourFacil\Core\Services\Pagamento\Getnet\Service\Tokenization;
use TourFacil\Core\Services\Pagamento\Getnet\Traits\Customer;
use TourFacil\Core\Services\Pagamento\Getnet\Traits\Device;
use TourFacil\Core\Services\Pagamento\Getnet\Traits\Funcoes;
use TourFacil\Core\Services\Pagamento\Getnet\Traits\Order;

/**
 * Class CreditCard
 * @package TourFacil\Core\Services\Pagamento\Getnet\Payment
 */
class CreditCard
{
    use Order, Customer, Device, Funcoes;

    /** PATH da URl na API */
    const _PATH = '/v1/payments/credit';

    /**
     * Formatado do array que ira para API
     *
     * @var array
     */
    protected $payload = [
        'seller_id' => '',
        'amount' => 0,
        'currency' => 'BRL',
        'customer' => [
            'billing_address' => []
        ],
        'credit' => [
            'delayed' => false,
            'save_card_data' => false,
            'transaction_type' => 'FULL',
            'number_installments' => 1,
            'soft_descriptor' => '',
            'card' => [
                'number_token' => '',
                'cardholder_name' => '',
                'security_code' => '',
                'expiration_month' => '',
                'expiration_year' => ''
            ]
        ]
    ];

    /**
     * Número do cartão de crédito
     *
     * @var int
     */
    protected $number_card = '';

    /**
     * Configura os campos fixos do payload
     *
     * @return $this
     */
    public function setDefaultFields()
    {
        $this->payload['seller_id'] = env('GETNET_SELLER_ID');

        return $this;
    }

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
     * Número de parcelas
     *
     * @param $number_installments
     * @return $this
     */
    public function setNumberInstallments(int $number_installments)
    {
        $this->payload['credit']['number_installments'] = $number_installments;

        if($number_installments > 1) {
            $this->payload['credit']['transaction_type'] = "INSTALL_NO_INTEREST";
        }

        return $this;
    }

    /**
     * Soft descriptor (tag para identificação do pagamento)
     *
     * @param string $soft_descriptor
     * @return $this
     */
    public function setSoftDescriptor(string $soft_descriptor)
    {
        $this->payload['credit']['soft_descriptor'] = $soft_descriptor;

        return $this;
    }

    /**
     * Numero do cartao
     *
     * @param string $number_card
     * @return $this
     */
    public function setNumberCard(string $number_card)
    {
        $this->number_card = self::onlyNumbers($number_card);

        return $this;
    }

    /**
     * Número do cartão tokenizado
     * @return $this
     * @throws \Exception
     */
    private function setNumberToken()
    {
        $this->payload['credit']['card']['number_token'] = Tokenization::getNumberToken($this->number_card);

        return $this->payload['credit']['card']['number_token'];
    }

    /**
     * Nome impresso no cartão
     *
     * @param string $cardholder_name
     * @return $this
     */
    public function setCardholderName(string $cardholder_name)
    {
        $this->payload['credit']['card']['cardholder_name'] = strtoupper($this->removeAccentuation($cardholder_name));

        return $this;
    }

    /**
     * Código de segurança
     *
     * @param string $security_code
     * @return $this
     */
    public function setSecurityCode(string $security_code)
    {
        $this->payload['credit']['card']['security_code'] = $security_code;

        return $this;
    }

    /**
     * Mês que expira o cartão
     *
     * @param string $expiration_month
     * @return $this
     */
    public function setExpirationMonth(string $expiration_month)
    {
        $this->payload['credit']['card']['expiration_month'] = $expiration_month;

        return $this;
    }

    /**
     * Ano que expira o cartão
     *
     * @param string $expiration_year
     * @return $this
     */
    public function setExpirationYear(string $expiration_year)
    {
        $this->payload['credit']['card']['expiration_year'] = $expiration_year;

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
        // Preenche os campos defaults
        self::setDefaultFields();

        // Gera o token com o número do cartão
        $this->setNumberToken();

        // Realiza a transação
        $response = $this->chargeOnCard();

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
     * @return mixed|object
     * @throws \Exception
     */
    private function chargeOnCard()
    {
        // Connect da API de pagamentos
        $request_connect = new RequestConnect();

        // Realiza a comunicação
        return $request_connect->connect_api(self::_PATH, Request::METHOD_POST, $this->payload);
    }
}
