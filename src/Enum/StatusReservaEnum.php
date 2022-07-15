<?php namespace TourFacil\Core\Enum;

/**
 * Class StatusReservaEnum
 * @package TourFacil\Core\Enum
 */
abstract class StatusReservaEnum
{
    // reserva finalizada e pronta pra utilizar
    const ATIVA = "ATIVA";

    // depois que a reserva já foi utilizada
    const UTILIZADO = "UTILIZADO";

    // cancelada ou estornada
    const CANCELADO = "CANCELADO";

    // aguardando o pagamento (debito ou boleto)
    const AGUARDANDO = "AGUARDANDO";

    // pagamento ok porem falta preencher dados da reserva
    const FINALIZAR = "FINALIZAR";

    // Foi  barrado pelo operadora do cartão ou anti fraude.
    const NEGADO = "NEGADO";

    const EXPIRADO = "EXPIRADO";

    const RESERVAS_VALIDAS = [
        self::ATIVA, self::UTILIZADO, self::FINALIZAR
    ];

    const STATUS = [
        self::ATIVA => "Ativo",
        self::UTILIZADO => "Utilizado",
        self::CANCELADO => "Cancelado",
        self::AGUARDANDO => "Aguardando",
        self::FINALIZAR => "Em andamento",
        self::NEGADO => "Negado",
        self::EXPIRADO => "Expirado",
    ];

    const CORES_STATUS = [
        self::ATIVA => "success",
        self::UTILIZADO => "info",
        self::CANCELADO => "danger",
        self::AGUARDANDO => "warning",
        self::FINALIZAR => "warning",
        self::NEGADO => "danger",
        self::EXPIRADO => "danger",
    ];
}
