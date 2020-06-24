<?php namespace TourFacil\Core\Enum;

/**
 * Class CanaisVendaEnum
 * @package TourFacil\Core\Enum
 */
abstract class CanaisVendaEnum
{
    const TOURFACIL = 1;

    const TERMINAIS_CDI = 2;

    const URL_TOURFACIL = "www.tourfacil.com.br";

    const URL_TERMINAIS_CDI = "www.tourfacil.com.br";

    const URL_TERMINAIS_LOGIN = "www.tourfacil.com.br/login";

    const URL_CACHE_CLEAR = "/reset-cache";

    const NOME_CANAIS = [
        self::URL_TOURFACIL => 'Tour Fácil',
        self::TERMINAIS_CDI => 'Terminais'
    ];
}
