<?php namespace TourFacil\Core\Services;

use TourFacil\Core\Models\ReservaPedido;

/**
 * Class ReservaService
 * @package TourFacil\Core\Services
 */
class ReservaService
{
    /**
     * Salva os acompanhantes na reserva somente para o terminal
     *
     * @param $pedido
     * @param $acompanhantes
     */
    public static function saveAcompanhantesTerminal($pedido, $acompanhantes)
    {
        // Recupera o servico do acompanhantes
        $servico_uuid = $acompanhantes['servico_uuid'];

        // Recupera as reservas do pedido
        $reservas_pedido = ReservaPedido::with('servico')
            ->where('pedido_id', $pedido->id)->get();

        //Recupera a reserva relacionada ao acompanhante
        $reserva = $reservas_pedido->first(function ($reserva) use ($servico_uuid) {
            return ($reserva->servico->uuid == $servico_uuid);
        });

        // Salva os dados dos acompanhantes na reserva
        $reserva->dadoClienteReservaPedido()->createMany($acompanhantes['dados']);
    }

    /**
     * Salva os dados adicionais da reserva
     *
     * @param $pedido
     * @param $dados_adicionais
     */
    public static function saveDadosAdicionaisTerminal($pedido, $dados_adicionais)
    {
        // Recupera o servico adquirido
        $servico_uuid = $dados_adicionais['servico_uuid'];

        // Recupera as reservas do pedido
        $reservas_pedido = ReservaPedido::with('servico')
            ->where('pedido_id', $pedido->id)->get();

        //Recupera a reserva relacionada ao acompanhante
        $reserva = $reservas_pedido->first(function ($reserva) use ($servico_uuid) {
            return ($reserva->servico->uuid == $servico_uuid);
        });

        // Salva os dados adicionais na reserva
        $reserva->campoAdicionalReservaPedido()->createMany($dados_adicionais['dados']);
    }
}
