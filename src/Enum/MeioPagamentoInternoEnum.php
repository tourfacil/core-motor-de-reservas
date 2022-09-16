<?php 

namespace TourFacil\Core\Enum;

/**
 * Class MeioPagamentoInternoEnum
 * @package TourFacil\Core\Enum
 */
abstract class MeioPagamentoInternoEnum
{
    const PAGARME = 'PAGARME';

    const CIELO = 'CIELO';

    const STONE = 'STONE';

    const BANCO_BRASIL = 'BANCO_BRASIL';

    const DINHEIRO = 'DINHEIRO';

    const MEIOS = [
        self::PAGARME => 'Pagarme',
        self::CIELO => 'Cielo',
        self::STONE => 'Stone',
        self::BANCO_BRASIL => 'Banco do Brasil',
        self::DINHEIRO => 'Dinheiro',
    ];
}
