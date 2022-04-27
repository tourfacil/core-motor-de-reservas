<?php

namespace TourFacil\Core\Services;

use TourFacil\Core\Enum\StatusFinalizacaoReservaEnum;

class FinalizacaoService
{
    /**
     * Verifica se o pedido esta finalizado
     * Caso for encontrada uma reserva não finalizada ele marca ela com uma FLAG
     *
     * @param $pedido
     * @return bool
     */
    public static function isPedidoFinalizado($pedido) {

        // Variavel que vai guardar se o pedido esta ou não finalizado
        $is_finalizado = true;

        // Todas as reservas do pedido
        $reservas = $pedido->reservas;

        // Passa em todas as reservas e verifica uma a uma se estão finalizadas
        foreach($reservas as $reserva) {

            if(self::isReservaFinalizada($reserva) == false) {
                $is_finalizado = false;

                // Seta uma flag na reserva para identificar que a mesma não esta finalizada
                $reserva->update(['finalizada' => StatusFinalizacaoReservaEnum::NAO_FINALIZADA]);
            }
        }

        return $is_finalizado;
    }

    /**
     * Verifica se a reserva esta finalizada
     *
     * @param $reserva
     * @return bool
     */
    public static function isReservaFinalizada($reserva) {

        $total_pessoas_adquiridas = $reserva->quantidade;
        $total_pessoas_cadastradas = $reserva->dadoClienteReservaPedido->count();

        $is_finalizada = true;

        if($reserva->servico->info_clientes == "SOLICITA_INFO_CLIENTES") {

            if($total_pessoas_adquiridas != $total_pessoas_cadastradas) {
                $is_finalizada = false;
            }
        }

        $quantidade_campos = $reserva->servico->camposAdicionaisAtivos->count();
        $quantidade_campos_cadastrados = $reserva->campoAdicionalReservaPedido->count();

        if($quantidade_campos != $quantidade_campos_cadastrados) {
            $is_finalizada = false;
        }

        return $is_finalizada;
    }
}
