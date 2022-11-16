<?php

namespace TourFacil\Core\Enum;

abstract class StatusEmailAvaliacaoEnum
{
    const ENVIADO = 'ENVIADO';
    const NAO_ENVIADO = 'NAO_ENVIADO';
    const NAO_ENVIAR = 'NAO_ENVIAR';

    const STATUS = [
        self::ENVIADO => 'Enviado',
        self::NAO_ENVIADO => 'Não enviado',
        self::NAO_ENVIAR => 'Não enviar',
    ];
}
