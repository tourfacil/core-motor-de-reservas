<?php

namespace TourFacil\Core\Enum;

/**
 * Class MetodoPagamentoEnum
 * @package TourFacil\Core\Enum
 */
abstract class MetodoPagamentoEnum
{
    const CARTAO_CREDITO = "CREDITO";

    const CARTAO_DEBITO = "DEBITO";

    const BOLETO = "BOLETO";

    const PIX = "PIX";

    const INTERNO = "INTERNO";

    const METHODS = [
        self::CARTAO_CREDITO => "Cartão de crédito",
        self::CARTAO_DEBITO => "Cartão de débito",
        self::BOLETO => "Boleto bancário"
    ];

    const CIELO = "CIELO";

    const GETNET_TERMINAIS = "GETNET_TERMINAIS";

    const GETNET = "GETNET";

    const MERCADO_PAGO = "MERCADO_PAGO";

    const WIRECARD = "WIRECARD";

    const GATEWAYS = [
        self::CIELO => "Cielo",
        self::GETNET => "Getnet",
        self::GETNET_TERMINAIS => "Getnet Terminais",
        self::MERCADO_PAGO => "Mercado Pago",
        self::WIRECARD => "Wirecard"
    ];
}
