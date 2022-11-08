<?php

namespace TourFacil\Core\Enum;

abstract class StatusEnum
{
    const ATIVA = 'ATIVA';

    const INATIVA = 'INATIVA';

    const PENDENTE = 'PENDENTE';

    const AGUARDANDO = 'AGUARDANDO';

    const STATUS = [
        self::ATIVA => 'Ativa',
        self::INATIVA => 'Inativa',
        self::PENDENTE => 'Pendente',
        self::AGUARDANDO => 'Aguardando',
    ];
}
