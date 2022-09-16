<?php 

namespace TourFacil\Core\Enum;

/**
 * Class MeioPagamentoInternoEnum
 * @package TourFacil\Core\Enum
 */
abstract class MetodoPagamentoInternoEnum
{
    const CARTAO_CREDITO = 'CARTAO_CREDITO';

    const CARTAO_DEBITO = 'CARTAO_DEBITO';

    const PIX = 'PIX';

    const BOLETO = 'BOLETO';

    const TRANSFERENCIA = 'TRANSFERENCIA'; 

    const DINHEIRO = 'DINHEIRO';

    const METODOS = [
        self::CARTAO_CREDITO => 'Cartão de Crédito',
        self::CARTAO_DEBITO => 'Cartão de Débito',
        self::PIX => 'Pix',
        self::BOLETO => 'Boleto',
        self::TRANSFERENCIA => 'Transferência',
        self::DINHEIRO => 'Dinheiro',
    ];
}
