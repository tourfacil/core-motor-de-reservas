<?php

namespace TourFacil\Core\Services\Pagamento;

use TourFacil\Core\Enum\MetodoPagamentoEnum;
use TourFacil\Core\Enum\StatusPixEnum;
use TourFacil\Core\Models\Cliente;
use TourFacil\Core\Models\Pedido;
use TourFacil\Core\Services\Pagamento\Pagarme\Pix;
use TourFacil\Core\Services\PedidoService;

/**
 * Class PixService
 * @package TourFacil\Core\Services\Pagamento
 */
class PixService
{

    /**
     * Realiza a cobranca no cartao de credito usando a Pagarme
     *
     * @param $array_pedido
     * @param $cliente
     * @return array
     */
    public static function gerarPixPagarme($array_pedido, $cliente)
    {

        $pix = new Pix();

        $pix->setOrderCode($array_pedido['codigo_pedido']);

        $pix->setCustomerName($cliente->nome);
        $pix->setCustomerDocument($cliente->cpf);
        $pix->setCustomerEmail($cliente->email);
        $pix->setCustomerPhone($cliente->telefone);

        $cliente = Cliente::where('id', $cliente->id)->with(['endereco'])->get()->first();

        $pix->setExpiresIn(env('PIX_TIMEOUT', 5));
        $pix->setItems($array_pedido);

        $codigo_pix = $pix->gerarCodigoPix();

        // Caso seja aprovado
        if ($codigo_pix['approved']) {
            return [
                "approved" => true,
                "payment_id" => $codigo_pix['payment_id'],
                "dados_pagamento" => [
                    'gateway' => MetodoPagamentoEnum::PAGARME,
                    'transacao' => $codigo_pix
                ],
            ];
        }

        // Caso haja algum tipo de erro
        return [
            "approved" => false,
            "message" => $codigo_pix['erro'],
            "dados_pagamento" => [
                'gateway' => MetodoPagamentoEnum::PAGARME,
                'transacao' => $codigo_pix
            ],
        ];
    }


    public static function getAndUpdateSituacaoPix(Pedido $pedido) {

        $status_pix = Pix::getStatus($pedido);
        
        if($status_pix == StatusPixEnum::PAGO) {
            PedidoService::setStatusPedidoPago($pedido);
        }

        if($status_pix == StatusPixEnum::EXPIRADO) {
            PedidoService::setStatusPedidoExpirado($pedido);
        }

        return $status_pix;
    }
}
