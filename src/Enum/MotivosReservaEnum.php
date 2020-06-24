<?php namespace TourFacil\Core\Enum;

/**
 * Class MotivosReservaEnum
 * @package TourFacil\Core\Enum
 */
abstract class MotivosReservaEnum
{
    const ALTERACAO_DATA = "ALTERACAO_DATA";

    const ALTERACAO_DATA_VIAGEM = "ALTERACAO_DATA_VIAGEM";

    const CANCELAMENTO_PARCIAL = "CANCELAMENTO_PARCIAL";

    const CANCELAMENTO_FORNECEDOR = "CANCELAMENTO_FORNECEDOR";

    const CANCELAMENTO_RECLAME_AQUI = "CANCELAMENTO_RECLAME_AQUI";

    const DESISTIU_DO_PASSEIO = "DESISTIU_DO_PASSEIO";

    const COMPROU_ERRADO = "COMPROU_ERRADO";

    const COMPRA_DUPLICADA = "COMPRA_DUPLICADA";

    const COBRANCA_INDEVIDA = "COBRANCA_INDEVIDA";

    const DATA_ERRADA = "DATA_ERRADA";

    const SEM_DISPONIBILIDADE = "SEM_DISPONIBILIDADE";

    const CANCELADA_MAU_TEMPO = "CANCELADA_MAU_TEMPO";

    const CANCELAMENTO_PESSOAL = "CANCELAMENTO_PESSOAL";

    const NAO_INFORMADO = "NAO_INFORMADO";

    const PROBLEMA_INTEGRACAO = "PROBLEMA_INTEGRACAO";

    const REMOCAO_ACOMPANHANTE = "REMOCAO_ACOMPANHANTE";

    const ADICAO_ACOMPANHANTE = "ADICAO_ACOMPANHANTE";

    const COMPRA_TESTE = "COMPRA_TESTE";

    const MOTIVOS = [
        self::ADICAO_ACOMPANHANTE => "Adição de um acompanhante",
        self::ALTERACAO_DATA => "Alteração na data de utilização",
        self::ALTERACAO_DATA_VIAGEM => "Alteração na data da viagem",
        self::CANCELADA_MAU_TEMPO => "Cancelada por mau tempo",
        self::CANCELAMENTO_PESSOAL => "Cancelada por motivos pessoais",
        self::CANCELAMENTO_FORNECEDOR => "Cancelamento pelo fornecedor",
        self::CANCELAMENTO_PARCIAL => "Cancelamento parcial",
        self::CANCELAMENTO_RECLAME_AQUI => "Cancelamento pelo RECLAMEAQUI",
        self::COBRANCA_INDEVIDA => "Cobrança indevida",
        self::COMPRA_DUPLICADA => "Compra em duplicidade",
        self::COMPRA_TESTE => "Compra de teste",
        self::COMPROU_ERRADO => "Cliente comprou errado",
        self::DATA_ERRADA => "Comprou para data errada",
        self::DESISTIU_DO_PASSEIO => "Cliente desistiu do passeio",
        self::NAO_INFORMADO => "Não informado",
        self::PROBLEMA_INTEGRACAO => "Problemas na integração com o parque",
        self::REMOCAO_ACOMPANHANTE => "Remoção de um acompanhante",
        self::SEM_DISPONIBILIDADE => "Indisponibilidade do serviço",
    ];

    /**
     * Motivos para cancelamento da reserva
     *
     * @return array
     */
    public static function motivosCancelamento()
    {
        return array_except(self::MOTIVOS, [
            self::ALTERACAO_DATA,
            self::REMOCAO_ACOMPANHANTE,
            self::ADICAO_ACOMPANHANTE,
            self::CANCELAMENTO_PARCIAL,
        ]);
    }
}
