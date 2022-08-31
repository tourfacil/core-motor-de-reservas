<?php
/*
 * Copyright (c) 2022. Lorem ipsum dolor sit amet, consectetur adipiscing elit.
 * Morbi non lorem porttitor neque feugiat blandit. Ut vitae ipsum eget quam lacinia accumsan.
 * Etiam sed turpis ac ipsum condimentum fringilla. Maecenas magna.
 * Proin dapibus sapien vel ante. Aliquam erat volutpat. Pellentesque sagittis ligula eget metus.
 * Vestibulum commodo. Ut rhoncus gravida arcu.
 */

namespace TourFacil\Core\Enum;

abstract class StatusAvaliacaoServicoEnum
{
    const PENDENTE = 'PENDENTE';

    const APROVADO = 'APROVADO';

    const REPROVADO = 'REPROVADO';

    const STATUS = [
        self::PENDENTE => 'Pendente',
        self::APROVADO => 'Aprovado',
        self::REPROVADO => 'Reprovado',
    ];

    const CORES_STATUS = [
        self::APROVADO => "success",
        self::REPROVADO => "danger",
        self::PENDENTE => "warning",
    ];
}
