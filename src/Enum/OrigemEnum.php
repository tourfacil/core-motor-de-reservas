<?php namespace TourFacil\Core\Enum;

/**
 * Class OrigemEnum
 * @package TourFacil\Core\Enum
 */
abstract class OrigemEnum
{
    const MOBILE = "MOBILE";

    const DESKTOP = "DESKTOP";

    const TERMINAL = "TERMINAL";

    const WEBSITE = "WEBSITE";

    const APLICATIVO = "APLICATIVO";

    const ORIGENS = [
        self::APLICATIVO => "Aplicativo",
        self::DESKTOP => "Desktop",
        self::TERMINAL => "Terminal",
        self::WEBSITE => "Website",
        self::MOBILE => "Celular"
    ];
}
