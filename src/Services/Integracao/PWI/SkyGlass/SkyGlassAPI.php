<?php

namespace TourFacil\Core\Services\Integracao\PWI\SkyGlass;

use Exception;
use GuzzleHttp\Client;

class SkyGlassAPI
{
    protected $login;

    protected $password;

    protected $base_url;

    public function __construct()
    {
        $this->login = 34242332000102;
        $this->password = 'B27H$^fw%ZvRsyS*R@';
        $this->base_url = 'https://integracaovendas.skyglasscanela.com.br/hom/api';
    }

    public function consultaAPI(String $metodo, String $url, Array $dados)
    {
        $token = $this->getAcessToken();

        $url = $this->base_url . $url;

        return $this->req($metodo, $url, $dados, $token);
    }

    private function getAcessToken()
    {
        // Url para consultar o token
        $url = $this->base_url . '/auth/login';

        // Dados necessarios para consultar o token
        $dados = [
            'usuario' => $this->login,
            'senha' => $this->password,
        ];

        // Resposta
        $response = $this->req("POST", $url, $dados);

        // Retorna a resposta
        return $response['data']['access_token'];
    }

    private function req(String $metodo, String $url, Array $dados, $authorization = '') {




        try {

            // Monta o array de headers
            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            // Verifica se a requisição ja tem código de autenticação. Caso tenha, adiciona aos headers
            if($authorization != '') {
                $headers['Authorization'] = 'Bearer ' . $authorization;
            }

            // Instancia o cliente HTTP Guzzle
            $client = new Client();

            // Monta e faz a requisição
            $response = $client->request($metodo, $url, [
                'headers' => $headers,
                'body' => json_encode($dados, true),
            ]);

            // Salva os dados de retorno em array associativo
            $data = json_decode($response->getBody()->getContents(), true);

            // Caso a requisição não retorne erros, retorna a resposta
            if(count($data['errors']) == 0) {

                return [
                    'error' => false,
                    'message' => 'Sucesso',
                    'data' => $data['result']
                ];

            // Caso a requisição retorna erros, retorna o erro
            } else {
                return [
                    'error' => true,
                    'message' => $data['errors'],
                    'code' => 'Erro interno',
                ];
            }

        // Caso ocorra algum problema entre os servidores, retorna
        } catch(Exception $exception) {

            return [
                'error' => true,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ];
        }
    }
}
