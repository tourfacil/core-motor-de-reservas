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

    const MINI_MUNDO = "MINI_MUNDO";

    const DREAMS = "DREAMS";

    const ALPEN = "ALPEN";

    const FANTASTIC_HOUSE = "FANTASTIC_HOUSE";

    const MATRIA = "MATRIA";

    const VILA_DA_MONICA = "VILA_DA_MONICA";

    const SKYGLASS = "SKYGLASS";

    const INTEGRACOES = [
        self::NAO => "Não possui",
        self::SNOWLAND => "Snowland",
        self::EXCEED_PARK => "Exceed Park",
        self::OLIVAS => "Olivas",
        self::MINI_MUNDO => "Mini Mundo",
        self::DREAMS => "Dreams",
        self::ALPEN => "Alpen Park",
        self::FANTASTIC_HOUSE => "Fantastic House",
        self::MATRIA => "Matria Park",
        self::VILA_DA_MONICA => "Vila da Monica",
        self::SKYGLASS => "Skyglass",
    ];

    const INTEGRACOES_PWI = [
        self::SKYGLASS => "Skyglass",
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
