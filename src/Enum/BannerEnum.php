<?php namespace TourFacil\Core\Enum;

/**
 * Class BannerEnum
 * @package TourFacil\Core\Enum
 */
abstract class BannerEnum
{
    const TIPO_SERVICO = "SERVICO";
    const TIPO_PROMOCAO = "PROMOCAO";

    const TIPOS = [
        self::TIPO_SERVICO => "Serviço",
        self::TIPO_PROMOCAO => "Promoção",
    ];
}
