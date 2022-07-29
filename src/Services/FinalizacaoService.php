<?php

namespace TourFacil\Core\Services;

use TourFacil\Core\Enum\StatusFinalizacaoReservaEnum;
use TourFacil\Core\Models\CampoAdicionalReservaPedido;
use TourFacil\Core\Models\DadoClienteReservaPedido;
use TourFacil\Core\Models\ReservaPedido;

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

    /**
     * Retorna os dados necessários uteis para a finalização de uma reserva
     * @param array $dados
     * @return array
     */
    public static function informacaoFinalizacao(Int $reserva_id) {

        $reserva = ReservaPedido::where('id', $reserva_id)
            ->with(['servico', 'servico.camposAdicionaisAtivos', 'quantidadeReserva', 'quantidadeReserva.variacaoServico'])
            ->get()
            ->first();

        return [
            'reserva' => $reserva,
            'quantidades' => $reserva->quantidadeReserva,
            'servico' => $reserva->servico,
        ];
    }

    /**
     * Cria os dados de viajantes e campos adicionais caso necessário e marca a reserva como finalizada
     * @param array $dados
     * @return mixed
     */
    public static function finalizarReserva(Array $dados) {
        // Busca os acompanhantes
        $acompanhantes = $dados['acompanhantes'];
        $campos_adicionais = $dados['campos_adicionais'];

        // Lista de acompanhantes e campos criados
        $acompanhantes_criados = [];
        $campos_adicionais_criados = [];

        // Guarda o reserva id para verificar as questões de finalização
        $reserva_id = null;

        // Percorre os acompanhantes e cria eles no banco de dados
        foreach($acompanhantes as $acompanhante) {
            $acompanhantes_criados[] = DadoClienteReservaPedido::create($acompanhante);
            $reserva_id = $acompanhante['reserva_pedido_id'];
        }

        // Percorre os campos adicionais e cria eles no banco de dados
        foreach($campos_adicionais as $campo_adicional) {
            $campos_adicionais_criados[] = CampoAdicionalReservaPedido::create($campo_adicional);
            $reserva_id = $campo_adicional['reserva_pedido_id'];
        }

        // Busca a reserva e o pedido para verificar a questão do envio de e-mails na finalização
        $reserva = ReservaPedido::find($reserva_id);

        $reserva->update(['finalizada' => StatusFinalizacaoReservaEnum::FINALIZADA]);

        return [
            'reserva' => $reserva,
            'acompanhantes' => $acompanhantes_criados,
            'campos_adicionais_criados' => $campos_adicionais_criados,
        ];
    }
}
