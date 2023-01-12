<?php 

namespace TourFacil\Core\Enum\Faturas;

abstract class TipoFaturaEnum
{
    const SEMANAL = 'SEMANAL';

    const QUINZENAL = 'QUINZENAL';

    const MENSAL = 'MENSAL';

    const TIPOS = [
        self::SEMANAL => 'Semanal',
        self::QUINZENAL => 'Quinzenal',
        self::MENSAL => 'Mensal'
    ];

}
