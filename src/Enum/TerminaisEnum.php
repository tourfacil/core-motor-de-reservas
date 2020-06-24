<?php namespace TourFacil\Core\Enum;

/**
 * Class TerminaisEnum
 * @package TourFacil\Core\Enum
 */
abstract class TerminaisEnum
{
    // Tempo para atualizar a página para home - Por inatividade - 10Min
    const TIME_REFRESH = 600;

    // Tempo para registrar o log - 6 Horas
    const TIME_LOG = 21600;

    // Dia para o pagamento das comissoes
    const DIA_PAGAMENTO = 15;

    // Quantidade de meses para adicionar na data da venda ate o pagamento
    const MES_PAGAMENTO = 2;

    const IMPLY = "IMPLY";

    const SCHALTER = "SCHALTER";

    const FABRICANTES = [
        self::IMPLY => "Terminais Imply",
        self::SCHALTER => "Terminais Schalter"
    ];

    const HISTORICO_CONEXAO = "CONEXAO";

    const HISTORICO_MANUTENCAO = "MANUTENCAO";

    const HISTORICO_VENDA = "VENDA";

    const TIPOS_HISTORICO = [
        self::HISTORICO_CONEXAO => "Conexão",
        self::HISTORICO_MANUTENCAO => "Manutenção",
        self::HISTORICO_VENDA => "Nova venda",
    ];
}
