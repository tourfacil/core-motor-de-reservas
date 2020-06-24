<?php namespace TourFacil\Core\Services;

use TourFacil\Core\Enum\ComissaoStatus;
use TourFacil\Core\Enum\MotivosReservaEnum;
use TourFacil\Core\Enum\VariacaoServicoEnum;
use TourFacil\Core\Models\AgendaDataServico;
use TourFacil\Core\Models\DebitoComissaoTerminal;
use TourFacil\Core\Models\HistoricoReservaPedido;

/**
 * Class QuantidadeReservaService
 * @package TourFacil\Core\Services
 */
class QuantidadeReservaService
{
    /**
     * Remove uma quantidade adquirida da reserva
     *
     * @param $quantidade_reserva
     */
    public static function removerQuantidadeReserva($quantidade_reserva)
    {
        // recupera o valor net do acompanhante
        $valor_net = $quantidade_reserva->valor_net;

        // recupera o valor net do acompanhante
        $valor_venda = $quantidade_reserva->valor_total;

        // Calcula o valor de venda da reserva
        $novo_valor_reserva = $quantidade_reserva->reservaPedido->valor_total - $valor_venda;

        // Nova quantidade na reserva
        $nova_quantidade = $quantidade_reserva->reservaPedido->quantidade - $quantidade_reserva->quantidade;

        // Zera os valores da quantidade adquirida
        $quantidade_reserva->update(['quantidade' => 0, 'valor_net' => 0, 'valor_total' => 0]);

        // Bloqueio consumido da reserva
        $bloqueio_consumido = $quantidade_reserva->reservaPedido->bloqueio_consumido;

        // Caso a variacao consumuda bloqueio remove o bloqueio consumido na agenda e na reserva
        if($quantidade_reserva->variacaoServico->consome_bloqueio == VariacaoServicoEnum::CONSOME_BLOQUEIO) {
            // Diminiu um do bloqueio consumido
            $bloqueio_consumido--;
            // Recupera a agenda da reserva
            $agenda = AgendaDataServico::find($quantidade_reserva->reservaPedido->agenda_data_servico_id);
            // Diminui a quantidade consumida e volta a disponibilidade que havia sido consumida
            $agenda->update(['disponivel' => ($agenda->disponivel + $quantidade_reserva->quantidade), 'consumido' => ($agenda->consumido - $quantidade_reserva->quantidade)]);
        }

        // Atualiza a quantidade e valores da reserva
        $quantidade_reserva->reservaPedido->update([
            "valor_total" => $novo_valor_reserva,
            "valor_net" => ($quantidade_reserva->reservaPedido->valor_net - $valor_net),
            "quantidade" => $nova_quantidade,
            "bloqueio_consumido" => $bloqueio_consumido
        ]);

        // Atualiza o valor final do pedido
        $quantidade_reserva->reservaPedido->pedido->update([
            "valor_total" => ($quantidade_reserva->reservaPedido->pedido->valor_total - $valor_venda)
        ]);

        // Caso tenha comissao do terminal de vendas
        if(is_object($quantidade_reserva->reservaPedido->comissaoTerminal)) {

            // calcula o novo valor da comissao
            $comissao = ($novo_valor_reserva / 100 * $quantidade_reserva->reservaPedido->servico->comissao_afiliado);

            // Diferenca da comissao
            $debito_comissao = $quantidade_reserva->reservaPedido->comissaoTerminal->comissao - $comissao;

            // Atualiza o valor da comissao e quantidade
            $quantidade_reserva->reservaPedido->comissaoTerminal->update([
                'quantidade' => $nova_quantidade, 'comissao' => $comissao
            ]);

            // Caso a comissao já estiver sido paga lança um debito para o proximo pagamento
            if($quantidade_reserva->reservaPedido->comissaoTerminal->status == ComissaoStatus::PAGO) {
                DebitoComissaoTerminal::create([
                    "terminal_id" => $quantidade_reserva->reservaPedido->comissaoTerminal->terminal_id,
                    "comissao_terminal_id" => $quantidade_reserva->reservaPedido->comissaoTerminal->id,
                    "valor" => $debito_comissao,
                    "status" => ComissaoStatus::AGUARDANDO,
                ]);
            }
        }

        // Deleta a quantidade adquirida na reserva
        $quantidade_reserva->delete();

        // Salva o historico de alteracao da reserva
        HistoricoReservaPedido::create([
            "pedido_id" => $quantidade_reserva->reservaPedido->pedido_id,
            "reserva_pedido_id" => $quantidade_reserva->reservaPedido->id,
            "motivo" => MotivosReservaEnum::CANCELAMENTO_PARCIAL,
            "user_id" => auth()->user()->id,
            "valor_fornecedor" => $valor_net,
            "valor" => $valor_venda,
        ]);
    }

    /**
     * Atualiza a quantidade adquirida na reserva
     *
     * @param $quantidade_reserva
     * @param $nova_quantidade
     * @param $qtd_removida
     */
    public static function updateQuantidadeReserva($quantidade_reserva, $nova_quantidade, $qtd_removida)
    {
        // recupera o valor net do acompanhante
        $valor_net = $quantidade_reserva->valor_net / $quantidade_reserva->quantidade;

        // recupera o valor net do acompanhante
        $valor_venda = $quantidade_reserva->valor_total / $quantidade_reserva->quantidade;

        // Valor de venda unitario x a nova quantidade
        $novo_valor_venda = $valor_venda * $nova_quantidade;

        // Valor net unitario x a nova quantidade
        $novo_valor_net = $valor_net * $nova_quantidade;

        // Atualiza a quantidade e valores da quantidade reserva
        $quantidade_reserva->update([
            'quantidade' => $nova_quantidade,
            'valor_net' => $novo_valor_net,
            'valor_total' => $novo_valor_venda,
        ]);

        // Bloqueio consumido da reserva
        $bloqueio_consumido = $quantidade_reserva->reservaPedido->bloqueio_consumido;

        // Caso a variacao consumuda bloqueio remove o bloqueio consumido na agenda e na reserva
        if($quantidade_reserva->variacaoServico->consome_bloqueio == VariacaoServicoEnum::CONSOME_BLOQUEIO) {
            // Diminui o bloqueio consumido na reserva
            $bloqueio_consumido = $nova_quantidade;
            // Recupera a agenda da reserva
            $agenda = AgendaDataServico::find($quantidade_reserva->reservaPedido->agenda_data_servico_id);
            // Diminui a quantidade consumida e volta a disponibilidade que havia sido consumida
            $agenda->update(['disponivel' => ($agenda->disponivel + $qtd_removida), 'consumido' => ($agenda->consumido - $qtd_removida)]);
        }

        // Atualiza a quantidade e valores da reserva
        $quantidade_reserva->reservaPedido->update([
            "valor_total" => ($quantidade_reserva->reservaPedido->valor_total - $novo_valor_venda),
            "valor_net" => ($quantidade_reserva->reservaPedido->valor_net - $novo_valor_net),
            "quantidade" => ($quantidade_reserva->reservaPedido->quantidade - $qtd_removida),
            "bloqueio_consumido" => $bloqueio_consumido
        ]);

        // Atualiza o valor final do pedido
        $quantidade_reserva->reservaPedido->pedido->update([
            "valor_total" => ($quantidade_reserva->reservaPedido->pedido->valor_total - $novo_valor_venda)
        ]);

        // Caso tenha comissao do terminal de vendas
        if(is_object($quantidade_reserva->reservaPedido->comissaoTerminal)) {

            // calcula o novo valor da comissao
            $debito_comissao = ($novo_valor_venda / 100 * $quantidade_reserva->reservaPedido->servico->comissao_afiliado);

            // Diferenca da comissao
            $comissao = $quantidade_reserva->reservaPedido->comissaoTerminal->comissao - $debito_comissao;

            // Atualiza o valor da comissao e quantidade
            $quantidade_reserva->reservaPedido->comissaoTerminal->update([
                'quantidade' => ($quantidade_reserva->reservaPedido->quantidade - $qtd_removida), 'comissao' => $comissao
            ]);

            // Caso a comissao já estiver sido paga lança um debito para o proximo pagamento
            if($quantidade_reserva->reservaPedido->comissaoTerminal->status == ComissaoStatus::PAGO) {
                DebitoComissaoTerminal::create([
                    "terminal_id" => $quantidade_reserva->reservaPedido->comissaoTerminal->terminal_id,
                    "comissao_terminal_id" => $quantidade_reserva->reservaPedido->comissaoTerminal->id,
                    "valor" => $debito_comissao,
                    "status" => ComissaoStatus::AGUARDANDO,
                ]);
            }
        }

        // Salva o historico de alteracao da reserva
        HistoricoReservaPedido::create([
            "pedido_id" => $quantidade_reserva->reservaPedido->pedido_id,
            "reserva_pedido_id" => $quantidade_reserva->reservaPedido->id,
            "motivo" => MotivosReservaEnum::CANCELAMENTO_PARCIAL,
            "user_id" => auth()->user()->id,
            "valor_fornecedor" => $novo_valor_net,
            "valor" => $novo_valor_venda,
        ]);
    }
}
