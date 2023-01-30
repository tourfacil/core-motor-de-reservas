<?php

namespace TourFacil\Core\Enum;

abstract class StatusCategoriaEnum
{
    const ATIVA = 'ATIVA';

    const INATIVA = 'INATIVA';

    const INVISIVEL = 'INVISIVEL';

    const STATUS = [
        self::ATIVA => 'Ativa',
        self::INATIVA => 'Inativa',
        self::INVISIVEL => 'Invisivel',
    ];
}
