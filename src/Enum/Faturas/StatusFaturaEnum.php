<?php 

namespace TourFacil\Core\Enum\Faturas;

abstract class StatusFaturaEnum
{
    const PENDENTE_APROVACAO = 'PENDENTE_APROVACAO';

    const PENDENTE_PAGAMENTO = 'PENDENTE_PAGAMENTO';

    const PAGA = 'PAGA';

    const CANCELADA = 'CANCELADA';

    const STATUS = [
        self::PENDENTE_APROVACAO => 'Aprovação pendente',
        self::PENDENTE_PAGAMENTO => 'Pagamento pendente',
        self::PAGA => 'Paga',
        self::CANCELADA => 'Cancelada',
    ];

    const CORES = [
        self::PENDENTE_APROVACAO => 'warning',
        self::PENDENTE_PAGAMENTO => 'danger',
        self::PAGA => 'success',
        self::CANCELADA => '',
    ];
}
