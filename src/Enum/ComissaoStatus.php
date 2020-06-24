<?php namespace TourFacil\Core\Enum;

/**
 * Class ComissaoStatus
 * @package TourFacil\Core\Enum
 */
abstract class ComissaoStatus
{
    const AGUARDANDO = "AGUARDANDO";

    const PAGO = "PAGO";

    const CANCELADO = "CANCELADO";

    const STATUS = [
        self::AGUARDANDO => "Aguardando",
        self::PAGO => "Pago",
        self::CANCELADO => "Cancelada",
    ];

    const CORES_STATUS = [
        self::AGUARDANDO => "warning",
        self::PAGO => "success",
        self::CANCELADO => "danger",
    ];
}
