<?php namespace TourFacil\Core\Enum;

/**
 * Class LogPedidoEnum
 * @package TourFacil\Core\Enum
 */
abstract class LogPedidoEnum
{
    const CRIACAO = "CRIACAO";

    const ALTERACAO = "ALTERACAO";

    const LOGS = [
        self::CRIACAO => "Criação",
        self::ALTERACAO => "Alteração"
    ];
}
