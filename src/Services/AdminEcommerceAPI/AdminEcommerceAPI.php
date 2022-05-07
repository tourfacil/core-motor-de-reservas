<?php

namespace TourFacil\Core\Services\AdminEcommerceAPI;

use GuzzleHttp\Client;
use TourFacil\Core\Models\Pedido;

abstract class AdminEcommerceAPI
{

    /**
     * Método exclusivo para quando o ADMIN fizer uma venda interna.
     * Quando se faz a venda interna via admin, o mesmo faz uma req HTTP solicitando que o ecommerce envie os e-mails.
     * Os e-mails enviados pelo ecommerce a pedido do admin serão o do cliente e o do fornecedor.
     *
     * Método feito para evitar duplicar códigos de ADMIN e ECOMMERCE e também porque atualmente o ADMIN não esta
     * preparado para fazer o envio dos mesmos
     *
     * @param Pedido $pedido
     * @return void
     */
    public static function solicitarEnvioDeEmailAposVendaInterna(Pedido $pedido) {

        // Monta um array de payload com o pedido_id
        $payload = [
            'pedido_id' => $pedido->id,
        ];

        // Manda a requisição para método de req para executar a operação
        $response = self::sendPostReq($payload);

        return;
    }


    /**
     * Método que faz a requisição HTTP para o ecommerce com os dados informados pelo usuário via parametro.
     * Este método serve apenas para requisições POST. Em caso de GET, PUT ou outros, usar o métodos especificos.
     *
     * @param array $data
     * @return \Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private static function sendPostReq(Array $data) {

        // Instancia o cliente do Guzzle
        $client = new Client();

        // Monta o array quer será enviado no POST
        $payload = [
            'key_code' => config('site.admin_ecommerce_api.key_code'),
            'data' => $data,
        ];

        // Recupera o link do ecommerce da .ENV
        $link = env('ECOMMERCE_URL') . '/api/admin-ecommerce-api/enviar-email-cliente-fornecedor';

        // Faz a requisição
        $response = $client->request('POST', $link, $payload);

        // Retorna o array de resposta da REQ
        return $response->getBody();
    }
}
