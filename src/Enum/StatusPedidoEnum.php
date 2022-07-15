<?php namespace TourFacil\Core\Enum;

/**
 * Class StatusPedidoEnum
 * @package TourFacil\Core\Enum
 */
abstract class StatusPedidoEnum
{
    const PAGO = "PAGO";

    const CANCELADO = "CANCELADO";

    const UTILIZADO = "UTILIZADO";

    const EXPIRADO = "EXPIRADO";

    const AGUARDANDO = "AGUARDANDO";

    const NEGADO = "NEGADO";

    const STATUS = [
        self::PAGO => "Pago",
        self::CANCELADO => "Cancelado",
        self::UTILIZADO => "Utilizado",
        self::EXPIRADO => "Expirado",
        self::AGUARDANDO => "Aguardando",
        self::NEGADO => "Negado",
    ];

    const COR_STATUS = [
        self::PAGO => "success",
        self::CANCELADO => "danger",
        self::EXPIRADO => "danger",
        self::AGUARDANDO => "warning",
        self::UTILIZADO => "info",
        self::NEGADO => 'danger',
    ];
}
