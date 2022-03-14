<?php

namespace TourFacil\Core\Services;

use Carbon\Carbon;
use TourFacil\Core\Enum\AgendaEnum;
use TourFacil\Core\Enum\ComissaoStatus;
use TourFacil\Core\Enum\ServicoEnum;
use TourFacil\Core\Enum\StatusPagamentoEnum;
use TourFacil\Core\Enum\StatusPedidoEnum;
use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Enum\TerminaisEnum;
use TourFacil\Core\Models\AgendaDataServico;
use TourFacil\Core\Models\Pedido;
use TourFacil\Core\Models\Servico;

/**
 * Class PedidoService
 * @package TourFacil\Core\Services
 */
class PedidoService
{
    /**
     * @var int
     */
    static $codigo_length = 8;

    /**
     * @var array
     */
    static $pedido = [];

    /**
     * Cria o array de pedido para inserção
     *
     * @param $servicos
     * @param $cliente
     * @param null $terminal
     * @return array
     */
    public static function prepareArrayPedido($servicos, $cliente, $terminal = null)
    {
        // Dados base do pedido
        self::$pedido = [
            "codigo_pedido" => self::gerarCodigoPedido($cliente),
            "valor_total" => 0,
            "split" => [],
            "reservas" => []
        ];

        // Monta o array para cada serviço
        foreach ($servicos as $servico_carrinho) {

            $variacoes = [];
            $total_reserva = 0;
            $total_net_reserva = 0;

            // Encontra o serviço
            $servico = Servico::with([
                'variacaoServico',
                'fornecedor.splits',
                'agendaServico.datasServico' => function ($query) use ($servico_carrinho) {
                    return $query->where('id', $servico_carrinho['agenda_selecionada']['data_servico_id']);
                }
            ])->where('uuid', $servico_carrinho['uuid'])->first();

            // Recupera o split de pagamento do fornecedor responsavel pelo servico
            $split_pagamento = $servico->fornecedor->splits->first(function ($split) use ($servico) {
                return ($split->canal_venda_id == $servico->canal_venda_id);
            });

            // Recupera as informacoes da data selecionada
            $data_servico = $servico->agenda->datasServico->first();

            // Configuracoes da agenda
            $configuracoes_agenda = $servico->agenda->substituicoes_agenda;

            // Substituicoes agenda
            $substitui_net = $configuracoes_agenda[AgendaEnum::SUBSTITUI_NET] ?? null;
            $substitui_venda = $configuracoes_agenda[AgendaEnum::SUBSTITUI_VENDA] ?? null;

            // Percorre as opções selecionadas
            foreach ($servico_carrinho['agenda_selecionada']['variacoes'] as $variacao_carrinho) {

                // Caso não tenha quantidade
                if ((!isset($variacao_carrinho['quantidade'])) || $variacao_carrinho['quantidade'] == 0) continue;

                // Pega a variacao do serviço
                $variacao_servico = $servico->variacaoServico->first(function ($item) use ($variacao_carrinho) {
                    return $item->id == $variacao_carrinho['variacao_id'];
                });

                // Valor net de cada variacao caso seja gratis coloca como 0
                $valor_net_variacao = ($variacao_servico->percentual > 0) ? ($variacao_servico->percentual / 100) * $data_servico->valor_net : 0;

                // Verifica se possui valores no NET para substituir
                if (is_array($substitui_net)) {
                    $valor_net_variacao = (string) number_format($valor_net_variacao, 2, ".", "");
                    $valor_net_variacao = ($substitui_net[$valor_net_variacao]) ?? $valor_net_variacao;
                }

                // Calcula o valor de venda da variacao
                $valor_venda_variacao = ($valor_net_variacao * $variacao_servico->markup);

                // Verifica se o servico possui corretagem de valor
                if ($servico->tipo_corretagem != ServicoEnum::SEM_CORRETAGEM && ($valor_venda_variacao > 0)) {

                    // Verifica se a corretagem é em percentual
                    if ($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_PORCENTUAL) {
                        $valor_venda_variacao += ($valor_venda_variacao / 100 * $servico->corretagem);
                    }

                    // Verifica se a corretagem é em valor fixo
                    if ($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_FIXA) {
                        $valor_venda_variacao += $servico->corretagem;
                    }
                }

                // Verifica se possui valores da venda para substituir
                if (is_array($substitui_venda)) {
                    $valor_venda_variacao = (string) number_format($valor_venda_variacao, 2, ".", "");
                    $valor_venda_variacao = $substitui_venda[$valor_venda_variacao] ?? $valor_venda_variacao;
                }

                /** Caso a variacao deva ser vendida por 1 real */
                if ($variacao_servico->percentual == 0 && $variacao_servico->markup == AgendaEnum::MARKUP_UM_REAL) {
                    $valor_venda_variacao = 1;
                }

                // Multiplaca o valor net pela quantidade selecionada
                $valor_net_variacao = $valor_net_variacao * $variacao_carrinho['quantidade'];

                // Multiplaca de venda pela quantidade selecionada
                $valor_venda_variacao = $valor_venda_variacao * $variacao_carrinho['quantidade'];

                // Soma ao valor net da reserva
                $total_net_reserva += $valor_net_variacao;

                // Soma ao valor total de venda da reserva
                $total_reserva += $valor_venda_variacao;

                // Variacaoes adquiradas do serviço
                $variacoes[] = [
                    "nome_variacao" => $variacao_servico->nome,
                    "variacao_servico_id" => $variacao_servico->id,
                    "quantidade" => $variacao_carrinho['quantidade'],
                    "valor_net" => (float) number_format($valor_net_variacao, 2, ".", ""),
                    "valor_total" => (float) number_format($valor_venda_variacao, 2, ".", ""),
                ];
            }

            // Soma ao valor total do pedido
            self::$pedido['valor_total'] += (float) number_format($total_reserva, 2, ".", "");

            // Dados da reserva
            self::$pedido['reservas'][] = [
                "servico" => $servico->nome,
                "foto_principal" => $servico_carrinho['foto_principal'],
                "servico_id" => $servico->id,
                "fornecedor_id" => $servico->fornecedor->id,
                "data_utilizacao" => $data_servico->data->format('d/m/Y'),
                "agenda_data_servico_id" => $data_servico->id,
                "comissao_afiliado" => $servico->comissao_afiliado,
                "valor_total" => (float) number_format($total_reserva, 2, ".", ""),
                "valor_net" => (float) number_format($total_net_reserva, 2, ".", ""),
                "quantidade" => $servico_carrinho['com_bloqueio'] + $servico_carrinho['sem_bloqueio'],
                "bloqueio_consumido" => $servico_carrinho['com_bloqueio'],
                "status" => StatusReservaEnum::ATIVA,
                "variacoes_reserva" => $variacoes,
                "acompanhantes" => $servico_carrinho['acompanhantes'] ?? null,
                "adicionais" => $servico_carrinho['adicionais'] ?? null,
            ];

            // Salva os dados para split de pagamento
            if (isset(self::$pedido['split'][$servico->fornecedor->id])) {
                self::$pedido['split'][$servico->fornecedor->id]['valor_net'] += (float) number_format($total_net_reserva, 2, ".", "");
            } elseif (is_object($split_pagamento)) {
                self::$pedido['split'][$servico->fornecedor->id] = [
                    "token" => $split_pagamento->token,
                    "valor_net" => (float) number_format($total_net_reserva, 2, ".", "")
                ];
            }
        }

        // Monta as comissoes do terminal
        self::comissaoTerminal($terminal);

        return self::$pedido;
    }

    /**
     * Comissao do terminal de venda
     *
     * @param $terminal
     */
    private static function comissaoTerminal($terminal)
    {
        // Caso nao seja venda no terminal
        if (is_null($terminal)) return;

        // Percorre as reserva para calcular a comissao do terminal
        foreach (self::$pedido['reservas'] as $index => $reserva) {

            // Calcula a comissao do terminal
            $comissao = ($reserva['valor_total'] / 100 * $reserva['comissao_afiliado']);

            // Calcula a data previa de pagamento da comissao
            $previa_pagamento = Carbon::today()->addMonths(TerminaisEnum::MES_PAGAMENTO)->day(TerminaisEnum::DIA_PAGAMENTO);

            // Salva no pedido a comissao de cada reserva / servico
            self::$pedido['reservas'][$index]['terminal'] = [
                'terminal_id' => $terminal->id,
                'quantidade' => $reserva['quantidade'],
                'comissao' => (float) number_format($comissao, 2, ".", ""),
                'previa_pagamento' => $previa_pagamento->format('Y-m-d')
            ];
        }
    }

    /**
     * Gera codigo do pedido baseado no ID do cliente
     *
     * @param $cliente
     * @return mixed|string
     */
    public static function gerarCodigoPedido($cliente)
    {
        // Numeros restantes que faltam para ser randomicos
        $restante = self::$codigo_length - strlen($cliente->id);

        // Prefixo do voucher é o ID do pedido
        $random_number = $cliente->id;

        // Monta o random number com o valores faltantes
        for ($i = 0; $i < $restante; $i++) {
            $random_number .= mt_rand(0, 9);
        }

        return $random_number;
    }

    /**
     * Gera um pedido para cartao de credito
     *
     * @param $pedido_array
     * @param $payment
     * @param $juros
     * @param $cliente
     * @param $canal_venda_id
     * @param $origem
     * @param $tipo_cartao
     * @return mixed
     */
    public static function gerarPedidoCartao($pedido_array, $payment, $juros, $cliente, $canal_venda_id, $origem, $tipo_cartao)
    {
        // Cria o pedido
        $pedido = Pedido::create([
            "cliente_id" => $cliente->id,
            "codigo" => $pedido_array['codigo_pedido'],
            "valor_total" => $pedido_array['valor_total'],
            "canal_venda_id" => $canal_venda_id,
            "juros" => $juros,
            "origem" => $origem,
            "status" => StatusPedidoEnum::PAGO,
            "status_pagamento" => StatusPagamentoEnum::AUTORIZADO,
            "metodo_pagamento" => $tipo_cartao,
        ]);

        // Salva os dados da transacao
        $pedido->transacaoPedido()->create([
            "transacao" => $payment
        ]);

        // Percorre cada servico para criar uma reserva
        foreach ($pedido_array["reservas"] as $reserva_carrinho) {

            // Verifica que se tem algum afiliado na venda
            $afiliado_session = session()->get('afiliado');
            $afiliado_reserva = null;

            if($afiliado_session != null) {
                $afiliado_reserva = $afiliado_session->id;
            }



            // Cria uma reserva para o servico selecionado
            $reserva = $pedido->reservas()->create([
                "servico_id" => $reserva_carrinho["servico_id"],
                "fornecedor_id" => $reserva_carrinho["fornecedor_id"],
                "agenda_data_servico_id" => $reserva_carrinho["agenda_data_servico_id"],
                "valor_total" => $reserva_carrinho["valor_total"],
                "valor_net" => $reserva_carrinho["valor_net"],
                "quantidade" => $reserva_carrinho["quantidade"],
                "bloqueio_consumido" => $reserva_carrinho["bloqueio_consumido"],
                "status" => StatusReservaEnum::ATIVA,
                "afiliado_id" => $afiliado_reserva,
            ]);

            // Percorre as variacoes compradas
            foreach ($reserva_carrinho["variacoes_reserva"] as $variacaoes_reserva) {

                // Salva as quantidade adquiradas no pedido
                $reserva->quantidadeReserva()->create([
                    "variacao_servico_id" => $variacaoes_reserva["variacao_servico_id"],
                    "quantidade" => $variacaoes_reserva["quantidade"],
                    "valor_total" => $variacaoes_reserva["valor_total"],
                    "valor_net" => $variacaoes_reserva["valor_net"],
                ]);
            }

            // Diminui a quantidade da disponibilidade na agenda
            $agenda_servico = AgendaDataServico::find($reserva_carrinho["agenda_data_servico_id"]);

            // Quantidade disponivel final diminuido a quantidade do carrinho
            $disponivel = $agenda_servico->disponivel - $reserva_carrinho["bloqueio_consumido"];

            // Status da agenda
            $status_agenda = ($disponivel >= 1) ? AgendaEnum::ATIVO : AgendaEnum::INDISPONIVEL;

            // Atualiza a quantidade disponivel na agenda
            $agenda_servico->update([
                "disponivel" => $agenda_servico->disponivel - $reserva_carrinho["bloqueio_consumido"],
                "consumido" => $agenda_servico->consumido + $reserva_carrinho["bloqueio_consumido"],
                "status" => $status_agenda
            ]);

            // Verifica se tem dados dos acompanhantes na reserva
            if (isset($reserva_carrinho['acompanhantes']) && is_array($reserva_carrinho['acompanhantes'])) {
                // Salva os dados dos acompanhantes na reserva
                $reserva->dadoClienteReservaPedido()->createMany($reserva_carrinho['acompanhantes']);
            }

            // Verifica se tem dados dados adicionais para a reserva
            if (isset($reserva_carrinho['adicionais']) && is_array($reserva_carrinho['adicionais'])) {
                // Salva os dados adicionais na reserva
                $reserva->campoAdicionalReservaPedido()->createMany($reserva_carrinho['adicionais']);
            }

            // Verifica se o pedido tem comissao de terminal
            if (isset($reserva_carrinho['terminal'])) {

                // Dados da comissao referente ao terminal
                $dados_comissao = $reserva_carrinho['terminal'];

                // Salva a comissao do terminal de venda
                $pedido->comissaoTerminal()->create([
                    'reserva_pedido_id' => $reserva->id,
                    'terminal_id' => $dados_comissao['terminal_id'],
                    'quantidade' => $dados_comissao['quantidade'],
                    'comissao' => $dados_comissao['comissao'],
                    'data_previsao' => $dados_comissao['previa_pagamento'],
                    'status' => ComissaoStatus::AGUARDANDO,
                ]);
            }
        }

        return $pedido;
    }

    /**
     * Gera um pedido para pix
     *
     * @param $pedido_array
     * @param $payment
     * @param $juros
     * @param $cliente
     * @param $canal_venda_id
     * @param $origem
     * @param $tipo_cartao
     * @return mixed
     */
    public static function gerarPedidoPix($pedido_array, $cliente, $canal_venda_id, $origem, $metodo_pagamento)
    {
        // Cria o pedido
        $pedido = Pedido::create([
            "cliente_id" => $cliente->id,
            "codigo" => $pedido_array['codigo_pedido'],
            "valor_total" => $pedido_array['valor_total'],
            "canal_venda_id" => $canal_venda_id,
            "juros" => 0,
            "origem" => $origem,
            "status" => StatusPedidoEnum::PAGO,
            "status_pagamento" => StatusPagamentoEnum::AUTORIZADO,
            "metodo_pagamento" => $metodo_pagamento,
        ]);

        // Salva os dados da transacao
        // $pedido->transacaoPedido()->create([
        //     "transacao" => $payment
        // ]);

        // Percorre cada servico para criar uma reserva
        foreach ($pedido_array["reservas"] as $reserva_carrinho) {

            // Cria uma reserva para o servico selecionado
            $reserva = $pedido->reservas()->create([
                "servico_id" => $reserva_carrinho["servico_id"],
                "fornecedor_id" => $reserva_carrinho["fornecedor_id"],
                "agenda_data_servico_id" => $reserva_carrinho["agenda_data_servico_id"],
                "valor_total" => $reserva_carrinho["valor_total"],
                "valor_net" => $reserva_carrinho["valor_net"],
                "quantidade" => $reserva_carrinho["quantidade"],
                "bloqueio_consumido" => $reserva_carrinho["bloqueio_consumido"],
                "status" => StatusReservaEnum::ATIVA
            ]);

            // Percorre as variacoes compradas
            foreach ($reserva_carrinho["variacoes_reserva"] as $variacaoes_reserva) {

                // Salva as quantidade adquiradas no pedido
                $reserva->quantidadeReserva()->create([
                    "variacao_servico_id" => $variacaoes_reserva["variacao_servico_id"],
                    "quantidade" => $variacaoes_reserva["quantidade"],
                    "valor_total" => $variacaoes_reserva["valor_total"],
                    "valor_net" => $variacaoes_reserva["valor_net"],
                ]);
            }

            // Diminui a quantidade da disponibilidade na agenda
            $agenda_servico = AgendaDataServico::find($reserva_carrinho["agenda_data_servico_id"]);

            // Quantidade disponivel final diminuido a quantidade do carrinho
            $disponivel = $agenda_servico->disponivel - $reserva_carrinho["bloqueio_consumido"];

            // Status da agenda
            $status_agenda = ($disponivel >= 1) ? AgendaEnum::ATIVO : AgendaEnum::INDISPONIVEL;

            // Atualiza a quantidade disponivel na agenda
            $agenda_servico->update([
                "disponivel" => $agenda_servico->disponivel - $reserva_carrinho["bloqueio_consumido"],
                "consumido" => $agenda_servico->consumido + $reserva_carrinho["bloqueio_consumido"],
                "status" => $status_agenda
            ]);

            // Verifica se tem dados dos acompanhantes na reserva
            if (isset($reserva_carrinho['acompanhantes']) && is_array($reserva_carrinho['acompanhantes'])) {
                // Salva os dados dos acompanhantes na reserva
                $reserva->dadoClienteReservaPedido()->createMany($reserva_carrinho['acompanhantes']);
            }

            // Verifica se tem dados dados adicionais para a reserva
            if (isset($reserva_carrinho['adicionais']) && is_array($reserva_carrinho['adicionais'])) {
                // Salva os dados adicionais na reserva
                $reserva->campoAdicionalReservaPedido()->createMany($reserva_carrinho['adicionais']);
            }

            // Verifica se o pedido tem comissao de terminal
            if (isset($reserva_carrinho['terminal'])) {

                // Dados da comissao referente ao terminal
                $dados_comissao = $reserva_carrinho['terminal'];

                // Salva a comissao do terminal de venda
                $pedido->comissaoTerminal()->create([
                    'reserva_pedido_id' => $reserva->id,
                    'terminal_id' => $dados_comissao['terminal_id'],
                    'quantidade' => $dados_comissao['quantidade'],
                    'comissao' => $dados_comissao['comissao'],
                    'data_previsao' => $dados_comissao['previa_pagamento'],
                    'status' => ComissaoStatus::AGUARDANDO,
                ]);
            }
        }

        return $pedido;
    }

    public static function gerarPedidoInterno($pedido_array, $cliente, $canal_venda_id, $origem, $metodo_pagamento) {
        // Cria o pedido
        $pedido = Pedido::create([
            "cliente_id" => $cliente->id,
            "codigo" => $pedido_array['codigo_pedido'],
            "valor_total" => $pedido_array['valor_total'],
            "canal_venda_id" => $canal_venda_id,
            "juros" => 0,
            "origem" => $origem,
            "status" => StatusPedidoEnum::PAGO,
            "status_pagamento" => StatusPagamentoEnum::AUTORIZADO,
            "metodo_pagamento" => $metodo_pagamento,
        ]);

        // Salva os dados da transacao
        // $pedido->transacaoPedido()->create([
        //     "transacao" => $payment
        // ]);

        // Percorre cada servico para criar uma reserva
        foreach ($pedido_array["reservas"] as $reserva_carrinho) {

            // Cria uma reserva para o servico selecionado
            $reserva = $pedido->reservas()->create([
                "servico_id" => $reserva_carrinho["servico_id"],
                "fornecedor_id" => $reserva_carrinho["fornecedor_id"],
                "agenda_data_servico_id" => $reserva_carrinho["agenda_data_servico_id"],
                "valor_total" => $reserva_carrinho["valor_total"],
                "valor_net" => $reserva_carrinho["valor_net"],
                "quantidade" => $reserva_carrinho["quantidade"],
                "bloqueio_consumido" => $reserva_carrinho["bloqueio_consumido"],
                "status" => StatusReservaEnum::ATIVA
            ]);

            // Percorre as variacoes compradas
            foreach ($reserva_carrinho["variacoes_reserva"] as $variacaoes_reserva) {

                // Salva as quantidade adquiradas no pedido
                $reserva->quantidadeReserva()->create([
                    "variacao_servico_id" => $variacaoes_reserva["variacao_servico_id"],
                    "quantidade" => $variacaoes_reserva["quantidade"],
                    "valor_total" => $variacaoes_reserva["valor_total"],
                    "valor_net" => $variacaoes_reserva["valor_net"],
                ]);
            }

            // Diminui a quantidade da disponibilidade na agenda
            $agenda_servico = AgendaDataServico::find($reserva_carrinho["agenda_data_servico_id"]);

            // Quantidade disponivel final diminuido a quantidade do carrinho
            $disponivel = $agenda_servico->disponivel - $reserva_carrinho["bloqueio_consumido"];

            // Status da agenda
            $status_agenda = ($disponivel >= 1) ? AgendaEnum::ATIVO : AgendaEnum::INDISPONIVEL;

            // Atualiza a quantidade disponivel na agenda
            $agenda_servico->update([
                "disponivel" => $agenda_servico->disponivel - $reserva_carrinho["bloqueio_consumido"],
                "consumido" => $agenda_servico->consumido + $reserva_carrinho["bloqueio_consumido"],
                "status" => $status_agenda
            ]);

            // Verifica se tem dados dos acompanhantes na reserva
            if (isset($reserva_carrinho['acompanhantes']) && is_array($reserva_carrinho['acompanhantes'])) {
                // Salva os dados dos acompanhantes na reserva
                $reserva->dadoClienteReservaPedido()->createMany($reserva_carrinho['acompanhantes']);
            }

            // Verifica se tem dados dados adicionais para a reserva
            if (isset($reserva_carrinho['adicionais']) && is_array($reserva_carrinho['adicionais'])) {
                // Salva os dados adicionais na reserva
                $reserva->campoAdicionalReservaPedido()->createMany($reserva_carrinho['adicionais']);
            }

            // Verifica se o pedido tem comissao de terminal
            if (isset($reserva_carrinho['terminal'])) {

                // Dados da comissao referente ao terminal
                $dados_comissao = $reserva_carrinho['terminal'];

                // Salva a comissao do terminal de venda
                $pedido->comissaoTerminal()->create([
                    'reserva_pedido_id' => $reserva->id,
                    'terminal_id' => $dados_comissao['terminal_id'],
                    'quantidade' => $dados_comissao['quantidade'],
                    'comissao' => $dados_comissao['comissao'],
                    'data_previsao' => $dados_comissao['previa_pagamento'],
                    'status' => ComissaoStatus::AGUARDANDO,
                ]);
            }
        }

        return $pedido;
    }
}
