<?php namespace TourFacil\Core\Enum;

/**
 * Class ValidacaoEnum
 * @package TourFacil\Core\Enum
 */
abstract class ValidacaoEnum
{
    const VALIDACAO_CODIGO = "CODIGO";

    const VALIDACAO_CPF = "CPF";

    const TIPOS_VALIDACAO = [
        self::VALIDACAO_CODIGO => "Código",
        self::VALIDACAO_CPF => "CPF",
    ];
}
