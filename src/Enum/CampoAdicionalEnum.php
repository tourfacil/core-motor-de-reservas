<?php namespace TourFacil\Core\Enum;

/**
 * Class CampoAdicionalEnum
 * @package TourFacil\Core\Enum
 */
abstract class CampoAdicionalEnum
{
    const REQUIRIDO = "SIM";

    const NAO_REQUIRIDO = "NAO";

    const OPCOES = [
        self::REQUIRIDO => "Sim",
        self::NAO_REQUIRIDO => "Não",
    ];
}
