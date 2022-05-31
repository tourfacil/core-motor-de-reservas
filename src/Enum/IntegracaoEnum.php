<?php namespace TourFacil\Core\Enum;

/**
 * Class IntegracaoEnum
 * @package TourFacil\Core\Enum
 */
abstract class IntegracaoEnum
{
    const NAO = "NAO";

    const SNOWLAND = "SNOWLAND";

    const BETO_CARRERO = "BETO_CARRERO";

    const EXCEED_PARK = "EXCEED_PARK";

    const OLIVAS = "OLIVAS";

    const INTEGRACOES = [
        self::NAO => "Não possui",
        self::SNOWLAND => "Snowland",
        self::EXCEED_PARK => "Exceed Park",
        self::OLIVAS => "Olivas",
    ];

    const INTEGRACOES_EXTERNAS = [
        self::SNOWLAND,
        self::BETO_CARRERO,
        self::OLIVAS,
    ];

    // Status do voucher na API
    const VOUCHER_ATIVO = "ATIVO";

    // Status do voucher na API
    const VOUCHER_CANCELADO = "CANCELADO";

    const STATUS_VOUCHER = [
        self::VOUCHER_ATIVO => "Voucher ativo",
        self::VOUCHER_CANCELADO => "Voucher cancelado",
    ];
}
