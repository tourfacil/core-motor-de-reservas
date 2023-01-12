<?php 

namespace TourFacil\Core\Enum\Faturas;

abstract class TipoPeriodoFaturaEnum
{
    const VENDA = 'VENDA';

    const UTILIZACAO = 'UTILIZACAO';

    const TIPOS_PERIODO = [
        self::VENDA => 'Venda',
        self::UTILIZACAO => 'Utilização',
    ];
}
