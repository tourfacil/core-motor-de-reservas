<?php namespace TourFacil\Core\Enum;

/**
 * Class FotoServicoEnum
 * @package TourFacil\Core\Enum
 */
abstract class FotoServicoEnum
{
    const PRINCIPAL = "PRINCIPAL";

    const NORMAL = "NORMAL";

    const LARGE = "LARGE";

    const MEDIUM = "MEDIUM";

    const SMALL = "SMALL";

    const TIPO_FOTO = [
        self::PRINCIPAL => "Principal",
        self::NORMAL => "Normal"
    ];
}
