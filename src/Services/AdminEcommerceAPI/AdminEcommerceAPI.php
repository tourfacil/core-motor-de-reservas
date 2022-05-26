<?php

namespace TourFacil\Core\Services\AdminEcommerceAPI;

use Exception;
use GuzzleHttp\Client;
use TourFacil\Core\Models\Pedido;
use Illuminate\Support\Facades\Log;

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

        // Flag para avisar que e-mail foi enviado
        $email_enviado = true;

        // Manda a requisição para método de req para executar a operação
        try {
            $response = self::sendPostReq($payload);
        } catch( Exception $e ) {
            $email_enviado = false;

            try {
                // Manda um email para avisar que a integração / Envio de e-mail falhou
                simpleMail(
                    'ATENÇÃO - Problema na Integração - Pedido #' . $pedido->codigo,
                    'Houve um problema na integração entre Admin e Tourfacil e por isso os e-mails e reservas integradas com parques do pedido #' . $pedido->codigo . ' não foram executados corretamente. Favor avisar aos fornecedores e integrar com os parques manualmente.',
                    config('site.admin_ecommerce_api.email_alerta')
                );
            } catch( Exception $e ) {
                Log::warning('Houve um erro no envio do e-mail de alerta sobre o não funcionamento da integração do pedido #' . $pedido->codigo);
            }

            Log::warning('Houve um problema na integração entre Admin e Tourfacil e por isso os e-mails e reservas integradas com parques do pedido #' . $pedido->codigo . ' não foram executados corretamente. Favor avisar aos fornecedores e integrar com os parques manualmente.');

        }
        
        return $email_enviado;
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
        $client = new Client(['verify' => false]);

        // Monta o array quer será enviado no POST
        $payload = [
            'form_params' => [
                'key_code' => config('site.admin_ecommerce_api.key_code'),
                'data' => $data,
            ],
        ];

        // Recupera o link do ecommerce da .ENV
        $link = env('ECOMMERCE_URL') . '/admin-ecommerce-api/enviar-email-cliente-fornecedor';

        // Faz a requisição
        $response = $client->request('POST', $link, $payload);

        // Retorna o array de resposta da REQ
        return $response;
    }
}
