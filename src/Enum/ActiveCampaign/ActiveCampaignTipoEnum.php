<?php namespace TourFacil\Core\Enum\ActiveCampaign;

/**
 * Class ActiveCampaign
 * @package TourFacil\Core\Enum\ActiveCampaign
 */
abstract class ActiveCampaignTipoEnum
{
    // Para clientes que serão cadastrados somente o e-mail. Exemplo: Newsletter.
    const SIMPLES = "SIMPLES";

    // Para clientes que terão seu e-mail cadastrado apos um pedido. Aí será cadastrado mais informações.
    const COM_PEDIDO = "COM_PEDIDO";

    const STATUS_DISPONIBILIDADE = [
        self::SIMPLES => 'Simples',
        self::COM_PEDIDO => "Infomações Pedido",
    ];
}
