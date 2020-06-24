<?php namespace TourFacil\Core\Enum;

/**
 * Class AgendaEnum
 * @package TourFacil\Core\Enum
 */
abstract class AgendaEnum
{
    const COMPARTILHA = "SIM";

    const NAO_COMPARTILHA = "NAO";

    CONST STATUS_COMPARTILHA = [
        self::COMPARTILHA => "Agenda compartilhada",
        self::NAO_COMPARTILHA => "Agenda não compartilhada",
    ];

    const COM_DISPONIBILIDADE = "COM_DISPONIBILIDADE";

    const SEM_DISPONIBILIDADE = "SEM_DISPONIBILIDADE";

    const BAIXA_DISPONIBILIDADE = "BAIXA_DISPONIBILIDADE";

    const STATUS_DISPONIBILIDADE = [
        self::COM_DISPONIBILIDADE => "Disponível",
        self::SEM_DISPONIBILIDADE => "Indisponível",
        self::BAIXA_DISPONIBILIDADE => "Baixa dispo.",
    ];

    const ATIVO = "ATIVO";

    const INDISPONIVEL = "INDISPONIVEL";

    const INATIVO = "INATIVO";

    // Quantidade total de dias para alertar sobre a disponibildade
    const DIAS_ALERTA = 5;

    const SEGUNDA = 1;

    const TERCA = 2;

    const QUARTA = 3;

    const QUINTA = 4;

    const SEXTA = 5;

    const SABADO = 6;

    const DOMINGO = 7;

    const DIAS_SEMANA = [
        self::SEGUNDA => "Segunda-feira",
        self::TERCA => "Terça-feira",
        self::QUARTA => "Quarta-feira",
        self::QUINTA => "Quinta-feira",
        self::SEXTA => "Sexta-feira",
        self::SABADO => "Sábado",
        self::DOMINGO => "Domingo",
    ];

    const SUBSTITUICAO_AGENDA = "SUBSTITUICAO_AGENDA";

    const SUBSTITUI_NET = "ALTERA_NET";

    const SUBSTITUI_VENDA = "ALTERA_VENDA";

    const SUBSTITUICOES_AGENDA = [
        self::SUBSTITUI_NET => "Substituição na tarifa NET",
        self::SUBSTITUI_VENDA => "Substituição no valor de venda"
    ];

    const MARKUP_UM_REAL = 1.00000;
}
