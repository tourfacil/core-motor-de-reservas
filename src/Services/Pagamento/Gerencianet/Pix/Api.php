<?php

namespace TourFacil\Core\Services\Pagamento\Gerencianet\Pix;

class Api
{

    /**
     * URL base do PSP
     * @var string
     */
    private $baseUrl;

    /**
     * Client ID do oAuth2 do PSP
     * @var string
     */
    private $clientId;

    /**
     * Client secret do oAuth2 do PSP
     * @var string
     */
    private $clientSecret;

    /**
     * Caminho absoluto até o arquivo do certificado
     * @var string
     */
    private $certificate;

    /**
     * Define os dados iniciais da classe
     * @param string $baseUrl
     * @param string $clientId
     * @param string $clientSecret
     * @param string $certificate
     */
    public function __construct($baseUrl, $clientId, $clientSecret, $certificate)
    {
        $this->baseUrl = $baseUrl;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->certificate = $certificate;
    }

    /**
     * Método responsavel por criar uma cobrança imediata
     * @param string $txid
     * @param array $request
     * @return array 
     */
    public function createCob($txid, $request)
    {
        return $this->send('PUT', '/v2/cob/' . $txid, $request);
    }

    /**
     * Método responsavel por consultar uma cobrança imediata
     * @param string $txid
     * @return array 
     */
    public function consultarCob($txid)
    {
        return $this->send('GET', '/v2/cob/' . $txid);
    }

    /**
     * Método responsavel por obter o token de acesso às APIs Pix
     * @return string 
     */
    private function getAccessToken()
    {
        $endpoint = $this->baseUrl . '/oauth/token';

        $headers = [
            'Content-type: application/json',
        ];

        $request = [
            'grant_type' => 'client_credentials'
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_USERPWD => $this->clientId . ":" . $this->clientSecret,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($request),
            CURLOPT_SSLCERT => $this->certificate,
            CURLOPT_SSLCERTPASSWD => '',
            CURLOPT_HTTPHEADER => $headers
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true)['access_token'] ?? '';
    }

    /**
     * Método responsavel por enviar requisições para o psp
     * @param string $method
     * @param string $resource
     * @param array $request
     * @return array 
     */
    private function send($method, $resource, $request = [])
    {
        $endpoint = $this->baseUrl . $resource;

        $headers = [
            'Cache-Control: no-cache',
            'Content-type: application/json',
            'Authorization: Bearer ' . $this->getAccessToken()
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSLCERT => $this->certificate,
            CURLOPT_SSLCERTPASSWD => '',
            CURLOPT_HTTPHEADER => $headers
        ]);

        switch ($method) {
            case 'POST':
            case 'PUT':
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($request));
                break;
        }

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}
