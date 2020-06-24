<?php namespace TourFacil\Core\Enum;

/**
 * Class StatusPagamentoEnum
 * @package TourFacil\Core\Enum
 */
abstract class StatusPagamentoEnum
{
    const AUTORIZADO = "AUTORIZADO";

    const ESTORNADO = "ESTORNADO";

    const NAO_AUTORIZADO = "NAO_AUTORIZADO";

    const PENDENTE = "PENDENTE";

    const STATUS = [
        self::AUTORIZADO => "Autorizado",
        self::ESTORNADO => "Estornado",
        self::NAO_AUTORIZADO => "Não autorizado",
        self::PENDENTE => "Pendente"
    ];

    const CORES_STATUS = [
        self::AUTORIZADO => "success",
        self::NAO_AUTORIZADO => "danger",
        self::ESTORNADO => "danger",
        self::PENDENTE => "warning",
    ];
}
