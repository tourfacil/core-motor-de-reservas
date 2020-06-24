<?php namespace TourFacil\Core\Enum;

/**
 * Class ServicoEnum
 * @package TourFacil\Core\Enum
 */
abstract class ServicoEnum
{
    // Dias de antecedencia default dos serviços
    const ANTECEDENCIA_DEFAULT = 1;

    // Solicita os dados dos clientes
    const SOLICITA_INFO_CLIENTES = "SOLICITA_INFO_CLIENTES";

    // Não solicita os dados dos clientes
    const SEM_INFO_CLIENTES = "SEM_INFO_CLIENTES";

    const INFO_CLIENTES = [
        self::SOLICITA_INFO_CLIENTES => "Documentos padrões (Nome e CPF)",
        self::SEM_INFO_CLIENTES => "Não necessita de identificação",
    ];

    // Status do serviço ativo
    const ATIVO = "ATIVO";

    // Status do serviço inativo
    const INATIVO = "INATIVO";

    // Status do serviço indisponivel
    const INDISPONIVEL = "INDISPONIVEL";

    // Status do serviço pendente
    const PENDENTE = "PENDENTE";

    // Status dos serviços
    const STATUS_SERVICO = [
        self::ATIVO => "Ativo",
        self::INATIVO => "Inativo",
        self::INDISPONIVEL => "Indisponível",
        self::PENDENTE => "Pendente"
    ];

    // Cores do status do servico
    const CORES_STATUS = [
        self::ATIVO => "success",
        self::INATIVO => "danger",
        self::INDISPONIVEL => "danger",
        self::PENDENTE => "info",
    ];

    // Corretagem de valor em percentual
    const CORRETAGEM_PORCENTUAL = "CORRETAGEM_PORCENTUAL";

    // Corretagem de valor fixa R$
    const CORRETAGEM_FIXA = "CORRETAGEM_FIXA";

    // Sem corretagem de valor
    const SEM_CORRETAGEM = "SEM_CORRETAGEM";

    // Tipos de corretagem do serviço
    const TIPOS_CORRETAGEM = [
        self::SEM_CORRETAGEM => "Sem corretagem",
        self::CORRETAGEM_PORCENTUAL => "Corretagem em percentual",
        self::CORRETAGEM_FIXA => "Corretagem fixa R$"
    ];
}
