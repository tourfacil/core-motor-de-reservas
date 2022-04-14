<?php

namespace TourFacil\Core\Services\Pagamento\Gerencianet\Pix;

abstract class PixService
{
    /**
     * Função responsavel por calcular se o PIX ja foi pago. Retorn true ou false
     *
     * @param $valor_para_conferencia
     * @param $txid
     * @return bool
     */
    public static function isPixPago($valor_para_conferencia, $txid) {

        // Busca as configurações do PIX no .env
        $rota_base = env('PIX_URL');
        $client_id = env('PIX_ID');
        $client_secret = env('PIX_SECRET');

        // Instancia a classe para fazer a req
        $payload = new Api(
            $rota_base,
            $client_id,
            $client_secret,
            env('PIX_CERT_PATH')
        );

        // Da o comando para fazer a pesquisa
        $response = $payload->consultarCob($txid);

        // Seta uma variavel para contar quanto da divida foi pago pelo PIX
        $valor_pago = 0;

        // Busca todos os PIX feitos para calcular se os valores pagos ja são suficientes para o valor do carrinho
        if(array_key_exists('pix', $response)) {
            foreach($response['pix'] as $pix) {
                $valor_pago += $pix['valor'];
            }
        }

        // Retorna se é ou não suficiente
        return $valor_pago >= $valor_para_conferencia;
    }
}
