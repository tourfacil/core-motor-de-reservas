<?php namespace TourFacil\Core\Enum;

/**
 * Class VariacaoServicoEnum
 * @package TourFacil\Core\Enum
 */
abstract class VariacaoServicoEnum
{
    const CONSOME_BLOQUEIO = "SIM";

    const NAO_CONSOME_BLOQUEIO = "NAO";

    const STATUS_BLOQUEIO = [
        self::CONSOME_BLOQUEIO => "Consome bloqueio",
        self::NAO_CONSOME_BLOQUEIO => "Não consome",
    ];

    const VARIACAO_DESTAQUE = "SIM";

    const VARIACAO_NORMAL = "NAO";

    const TIPO_VARIACAO = [
        self::VARIACAO_DESTAQUE => "Usar como preço base",
        self::VARIACAO_NORMAL => "Não usar como preço base",
    ];
}
