<?php
/*
 * Copyright (c) 2022. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace TourFacil\Core\Services\Integracao\PWI;

use Exception;
use GuzzleHttp\Client;
use TourFacil\Core\Enum\TipoRequisicaoEnum;

class PWIAPI
{
    /**
     * @var int
     */
    protected $login;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string
     */
    protected $base_url;

    /**
     *
     */
    public function __construct()
    {
        $this->login = 34242332000102;
        $this->password = 'B27H$^fw%ZvRsyS*R@';
        $this->base_url = 'https://integracaovendas.skyglasscanela.com.br/prod/api';
    }

    public function efetuarCompra(Array $dados) {
        return $this->consultaAPI(TipoRequisicaoEnum::POST, '/venda/incluir', $dados);
    }

    public function consultarProdutos()
    {
        return $this->consultaAPI(TipoRequisicaoEnum::GET, "/produto/lista", []);
    }

    public function consultarVenda($id)
    {
        return $this->consultaAPI(TipoRequisicaoEnum::GET, "/venda/$id", []);
    }

    public function consultarVendas(String $data_inicial, String $data_final)
    {
        return $this->consultaAPI(TipoRequisicaoEnum::GET, "/venda/lista/$data_inicial/$data_final", []);
    }

    public function consultarSaldo()
    {
        return $this->consultaAPI(TipoRequisicaoEnum::POST, '/credito/saldo', []);
    }

    /**
     * Responsavel por ativar a API e fazer as chamadas HTTP
     * Método utilizado por todos outros
     *
     * @param String $metodo
     * @param String $url
     * @param array $dados
     * @return array
     */
    private function consultaAPI(String $metodo, String $url, Array $dados)
    {
        $token = $this->getAcessToken();

        $url = $this->base_url . $url;

        return $this->req($metodo, $url, $dados, $token);
    }

    /**
     * Método responsavel por fazer chamada ao parque e recuperar o token necessário para as demais requisições
     *
     * @return mixed
     */
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
        $response = $this->req(TipoRequisicaoEnum::POST, $url, $dados);

        // Retorna a resposta
        return $response['data']['access_token'];
    }

    private function checkoutAPI()
    {
        // Url para checkout
        $url = $this->base_url . '/auth/logout';

        // Dados para fazer checkout
        $dados = [
            'usuario' => $this->login,
            'senha' => $this->password,
        ];

        try {

            // Resposta
            $response = $this->req(TipoRequisicaoEnum::POST, $url, $dados);
            return true;

        } catch ( Exception $exception) {
            return false;
        }
    }

    /**
     * Método responsavel por todas as requisições.
     * Todos os demais métodos usam este para fazer chamadas para a API
     *
     * @param String $metodo
     * @param String $url
     * @param array $dados
     * @param $authorization
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
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
