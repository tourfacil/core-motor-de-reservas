<?php

namespace TourFacil\Core\Services\Pagamento\Gerencianet\Pix;

use Mpdf\QrCode\QrCode;
use Mpdf\QrCode\Output;

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

    public static function gerarCodigoPix($cliente, $valor_pix) {

        $rota_base = env('PIX_URL');
        $client_id = env('PIX_ID');
        $client_secret = env('PIX_SECRET');

        $payload = new Api(
            $rota_base,
            $client_id,
            $client_secret,
            env('PIX_CERT_PATH')
        );

        $cliente['cpf'] = str_replace(".", "", $cliente['cpf']);
        $cliente['cpf'] = str_replace("-", "", $cliente['cpf']);

        $requisicao = [
            'calendario' => [
                'expiracao' => 3600
            ],
            'devedor' => [
                'nome' => $cliente['nome'],
                'cpf' => $cliente['cpf'],
            ],
            'valor' => [
                'original' => number_format($valor_pix, "2", ".", ""),
            ],
            'chave' => '92a2ada0-1871-4c5e-af1b-8453e36215fd',
            'solicitacaoPagador' => "TourFacil"
        ];

        // Gera um número para o COB
        $numero_bytes = 13;
        $restultado_bytes = random_bytes($numero_bytes);
        $txid = bin2hex($restultado_bytes);

        $result = $payload->createCob($txid, $requisicao);

        // Caso a geração do COB falhar, retorna false
        if(array_key_exists("location", $result) == false) {
            return false;
        }

        $pix = (new Payload)
            ->setMerchantName('Tour Facil')
            ->setMerchantCity('RS')
            ->setAmount($result['valor']['original'])
            ->setTxid('***')
            ->setUrl($result['location'])
            ->setUniquePayment(true);

        $codigo_pix = $pix->getPayload();
        $qrcode_pix = new QrCode($codigo_pix);
        $qrcode_image = base64_encode((new Output\Png)->output($qrcode_pix, 300));

        return [
            'txid' => $txid,
            'codigo_pix' => $codigo_pix,
            'qrcode_pix' => $qrcode_pix,
            'qrcode_image' => $qrcode_image,
        ];
    }

    public static function cancelarPixSessao() {
        session()->forget('pix');
        return;
    }
}
