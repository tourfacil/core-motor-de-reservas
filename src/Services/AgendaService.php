<?php namespace TourFacil\Core\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use TourFacil\Core\Enum\AgendaEnum;
use TourFacil\Core\Enum\CanaisVendaEnum;
use TourFacil\Core\Enum\ServicoEnum;
use TourFacil\Core\Enum\VariacaoServicoEnum;
use TourFacil\Core\Models\AgendaDataServico;
use TourFacil\Core\Models\AgendaServico;
use TourFacil\Core\Models\ReservaPedido;
use TourFacil\Core\Models\Servico;
use TourFacil\Core\Services\RegraServico\ValorExcecaoDiaService;

/**
 * Class AgendaService
 * @package TourFacil\Core\Services
 */
class AgendaService
{
    /**
     * Datas da agenda para o calendario administrativo
     *
     * @param $agenda_id
     * @return array
     */
    public static function calendarioAdmin($agenda_id)
    {
        $last_30 = Carbon::today()->subDays(30);

        // Recupera a agenda com as datas e os servicos
        $agenda = AgendaServico::with([
            'datasServico' => function($query) use ($last_30) {
                return $query->where('data', '>=', $last_30);
            }
        ])->find($agenda_id);

        $array_datas = [];

        // Disponibilidade baixa informada na agenda
        $dispo_baixa = $agenda->disponibilidade_minima;

        // Disponibilidade media e dividido por 2 e somado a baixa
        $dispo_media = (int) ($agenda->disponibilidade_minima / 2 + $dispo_baixa);

        // Retorna a classe de acordo com a disponiblidade
        $class_names = function ($quantidade) use ($dispo_media, $dispo_baixa) {
            // Classes de acordo com a disponibilidade
            if($quantidade <= $dispo_baixa) {
                return "fc-bg-red-cad";
            } elseif ($quantidade <= $dispo_media) {
                return 'fc-bg-orange-cad';
            }
            return 'fc-bg-green-cad';
        };

        // Percorre as datas da agenda
        foreach ($agenda->datasServico as $date) {
            // Monta o array com as datas do calendario
            $array_datas[] = [
                "id" => $date->id,
                "title" => "Data {$date->data->format('d/m/Y')}",
                "mes" => mesPT($date->data->format('m')),
                "disponivel" => $date->disponivel,
                "consumido" => $date->consumido,
                "valor_net" => "R$ " . $date->valor_net_br,
                "valor_venda" => "R$ " . $date->valor_venda_br,
                "start" => "{$date->data->format('Y-m-d')}",
                "className" => $class_names($date->disponivel),
                "view" => route("app.agenda.datas.view", $date->id)
            ];
        }

        return $array_datas;
    }

    /**
     * Detalhes da data para o administrativo
     * Monta o array por data com os valores de venda de cada variacao
     *
     * @param $data_id
     * @return array
     */
    public static function detalhesDataAdmin($data_id)
    {
        $data = AgendaDataServico::with('agendaServico.servicos.variacaoServicoAtivas')->find($data_id);

        // Configuracoes da agenda
        $configuracoes_agenda = $data->agendaServico->substituicoes_agenda;

        // Substituicoes agenda
        $substitui_net = $configuracoes_agenda[AgendaEnum::SUBSTITUI_NET] ?? null;
        $substitui_venda = $configuracoes_agenda[AgendaEnum::SUBSTITUI_VENDA] ?? null;

        // Dados da agenda
        $dados = [
            "data_id" => $data->id,
            "title" => "Data {$data->data->format('d/m/Y')}",
            "disponivel" => $data->disponivel,
            "consumido" => $data->consumido,
            "valor_net" => $data->valor_net_br,
            "valor_venda" => $data->valor_venda_br,
            "servicos" => [],
            'agenda_d' => $data->agenda_servico
        ];

        // Percorre cada servico
        foreach ($data->agendaServico->servicos as $count => $servico) {
            // Array das variacoes do servico
            $variacoes = [];

            // Monta os valores por variacao
            foreach ($servico->variacaoServicoAtivas as $variacao) {

                // Valor net de cada variacao
                $net_variacao =  ($variacao->percentual / 100) * $data->valor_net;

                // Verifica se possui valores no NET para substituir
                if(is_array($substitui_net)) {
                    $net_variacao = (string) number_format($net_variacao, 2, ".", "");
                    $net_variacao = (isset($substitui_net[$net_variacao])) ? (float) $substitui_net[$net_variacao] : (float) $net_variacao;
                }

                // Valor de venda da variacao
                $venda_variacao = $net_variacao * $variacao->markup;

                // Verifica se o servico possui corretagem de valor
                if($servico->tipo_corretagem != ServicoEnum::SEM_CORRETAGEM && ($venda_variacao > 0)) {

                    // Verifica se a corretagem é em percentual
                    if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_PORCENTUAL) {
                        $venda_variacao += ($venda_variacao / 100 * $servico->corretagem);
                    }

                    // Verifica se a corretagem é em valor fixo
                    if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_FIXA) {
                        $venda_variacao += $servico->corretagem;
                    }
                }

                // Verifica se possui valores da venda para substituir
                if(is_array($substitui_venda)) {
                    $venda_variacao = (string) number_format($venda_variacao, 2, ".", "");
                    $venda_variacao = (isset($substitui_venda[$venda_variacao])) ? (float) $substitui_venda[$venda_variacao] : (float) $venda_variacao;
                }

                // Caso a variacao deva ser vendida por 1 real
                if($variacao->percentual == 0 && $variacao->markup == AgendaEnum::MARKUP_UM_REAL) {
                    $venda_variacao = 1;
                }

                // Dados para o array
                $variacoes[] = [
                    "variacao" => $variacao->nome,
                    "valor_venda" => "R$ " . formataValor($venda_variacao),
                    "valor_net" => "R$ " . formataValor($net_variacao),
                    "porcentagem" => $variacao->percentual . "%",
                    "comissao" => (100 - porcentagemComissao($venda_variacao, $net_variacao))
                ];
            }

            // Coloca no array as informacoes do servico
            $dados["servicos"][] = [
                "servico" => $servico->nome,
                "nome_tab" => CanaisVendaEnum::NOME_CANAIS[$servico->canal_venda_id] ?? "Serviço " . ++$count,
                "servico_id" => $servico->id,
                "variacoes" => $variacoes
            ];
        }

        return $dados;
    }

    /**
     * Calendario de disponibilidade para o site
     *
     * @param $uuid
     * @return array
     */
    public static function disponibilidadeSite($uuid)
    {
        // Data de hoje
        $today = Carbon::today();

        // Dados do serviço
        $servico = Servico::with('variacaoServicoAtivas', 'agendaServico', 'camposAdicionaisAtivos')
            ->where('uuid', $uuid)->select(['id', 'uuid', 'nome', 'info_clientes', 'antecedencia_venda', 'hora_maxima_antecedencia'])->first();

        // Busca se o produto tem alguma regra de antecedencia
        // Não é a antecedencia de quantos dias para frente pode vender e sim a que muda os valores
        $regra_antecedencia = ValorExcecaoDiaService::getRegraAtecedenciaServicoAtiva($servico);

        if (is_object($servico)) {

            // Pega o desconto caso tenha
            $desconto = $servico->descontoAtivo;

            // Caso o serviço tenha uma hora máxima para a venda no dia
            if($servico->hora_maxima_antecedencia) {

                // Logica para quebrar a hora máximo do produto na venda
                $hora_servico_array = explode(':', $servico->hora_maxima_antecedencia);
                $hora_antecedencia = $hora_servico_array[0];
                $minuto_antecedencia = $hora_servico_array[1];

                // Monta a data de hoje com hora de antecedencia e momento atual
                $hora_antecedencia = Carbon::today()->hour($hora_antecedencia)->minute($minuto_antecedencia);
                $hora_agora = Carbon::now();

                // Verifica se Agora ja é maior que a antecedencia de horas do serviço
                // Caso seja, aumenta a antecedencia para 1
                if($hora_agora->isAfter($hora_antecedencia)) $servico->antecedencia_venda++;
            }

            // Adiciona a antecedencia de venda para buscar a agenda
            $today->addDays($servico->antecedencia_venda)->startOfDay();

            // Recupera a agenda do serviço junto com a antecedencia de venda
            $agenda_servico = AgendaServico::with([
                'datasServico' => function ($q) use ($today) {
                    return $q->where('data', '>=', $today->format('Y-m-d'))
                        ->where('disponivel', '>', 0)->where('status', AgendaEnum::ATIVO);
                }
            ])->find($servico->agenda->id);

            // Configuracoes da agenda
            $configuracoes_agenda = $agenda_servico->substituicoes_agenda;

            // Substituicoes agenda
            $substitui_net = $configuracoes_agenda[AgendaEnum::SUBSTITUI_NET] ?? null;
            $substitui_venda = $configuracoes_agenda[AgendaEnum::SUBSTITUI_VENDA] ?? null;

            // Array para o calendario
            $retorno = [
                'uuid' => $servico->uuid,
                'nome' => $servico->nome,
                'events' => [],
                'disponibilidade' => [],
                'campos_adicionais' => $servico->camposAdicionaisAtivos,
                'necessita_identificacao' => ($servico->info_clientes == ServicoEnum::SOLICITA_INFO_CLIENTES)
            ];
            $cont = 0;
            // Monta o array com as datas
            foreach ($agenda_servico->datasServico as $data_agenda) {

                // Se tem disponibilidade
                if ($data_agenda->disponivel >= 1) {

                    $variacaoes = [];
                    $valor_venda_data = 0;

                    // Calcula o valor de venda das variacoes
                    foreach ($servico->variacaoServicoAtivas as $variacao) {

                        // Valor net de cada variacao
                        $net_variacao =  ($variacao->percentual / 100) * $data_agenda->valor_net;

                        // Verifica se possui valores no NET para substituir
                        if(is_array($substitui_net)) {
                            $net_variacao = (string) number_format($net_variacao, 2, ".", "");
                            $net_variacao = ($substitui_net[$net_variacao]) ?? $net_variacao;
                        }

                        // Verifica se há uma regra de antecedencia para valor diferenciado e aplica
                        // Se não houver mantem o memso valor
                        $net_variacao = ValorExcecaoDiaService::aplicarValorRegraAntecedencia($regra_antecedencia, $data_agenda->data, $net_variacao);

                        // Valor de venda da variacao
                        $venda_variacao = $net_variacao * $variacao->markup;

                        // Verifica se o servico possui corretagem de valor
                        if($servico->tipo_corretagem != ServicoEnum::SEM_CORRETAGEM && ($venda_variacao > 0)) {

                            // Verifica se a corretagem é em percentual
                            if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_PORCENTUAL) {
                                $venda_variacao += ($venda_variacao / 100 * $servico->corretagem);
                            }

                            // Verifica se a corretagem é em valor fixo
                            if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_FIXA) {
                                $venda_variacao += $servico->corretagem;
                            }
                        }

                        // Verifica se possui valores da venda para substituir
                        if(is_array($substitui_venda)) {
                            $venda_variacao = (string) number_format($venda_variacao, 2, ".", "");
                            $venda_variacao = $substitui_venda[$venda_variacao] ?? $venda_variacao;
                        }

                        // Salva o maior valor de venda ou salva o valor da variacao destaque
                        $valor_venda_data = (
                            $venda_variacao > $valor_venda_data || $variacao->destaque == VariacaoServicoEnum::VARIACAO_DESTAQUE
                        ) ? $venda_variacao : $valor_venda_data;

                        // Caso a variacao deva ser vendida por 1 real
                        if($variacao->percentual == 0 && $variacao->markup == AgendaEnum::MARKUP_UM_REAL) {
                            $venda_variacao = 1;
                        }

                        // Guarda o valor sem desconto
                        $valor_original = $venda_variacao;

                        // Aplica o desconto caso tenha
                        $venda_variacao = DescontoService::aplicarDescontoValor($desconto, $venda_variacao);


                        // Dados para o array
                        $variacaoes[] = [
                            'variacao_id' => $variacao->id,
                            'variacao' => $variacao->nome,
                            'descricao' => $variacao->descricao,
                            'bloqueio' => $variacao->consome_bloqueio,
                            'valor_venda' => (float) number_format($venda_variacao, 2, ".", ""),
                            'valor_venda_original' => (float) number_format($valor_original, 2, ".", ""),
                            'valor_venda_brl' => formataValor($venda_variacao),
                            'valor_venda_brl_original' => formataValor($valor_original),
                        ];
                    }

                    // Obtem a variação com o valor de venda mais alto para exibir no calendario
                    $valor_venda_mais_alto = 0;
                    foreach($variacaoes as $variacao) {

                        if($valor_venda_mais_alto < $variacao['valor_venda']) {
                            $valor_venda_mais_alto = $variacao['valor_venda'];
                        }
                    }

                    $data_agenda_valor_venda_original = $valor_venda_mais_alto;
                    $valor_venda_data_original = $valor_venda_mais_alto;
                    $data_agenda_valor_venda = $valor_venda_mais_alto;
                    $valor_venda_data = $valor_venda_mais_alto;

                    // Dados da agenda
                    $retorno['disponibilidade'][] = [
                        'data_servico_id' => $data_agenda->id,
                        'data' => $data_agenda->data->format('Y-m-d'),
                        'valor_venda' => (float) number_format($data_agenda_valor_venda, 2, ".", ""),
                        'valor_venda_original' => (float) number_format($data_agenda_valor_venda_original, 2, ".", ""),
                        'valor_venda_brl' => formataValor($valor_venda_data),
                        'valor_venda_brl_original' => formataValor($valor_venda_data_original),
                        'variacoes' => $variacaoes,
                        'disponibilidade' => $data_agenda->disponivel
                    ];

                    // Array para o calendario
                    $retorno['events'][] = [
                        'date' => $data_agenda->data->format('Y-m-d') . " 00:00:00",
                        'text' => "R$ " . formataValor($valor_venda_data),
                        'text_original' => "R$ " . formataValor($valor_venda_data_original),
                    ];
                }
            }

            return $retorno;

        }

        return ['events' => []];
    }

    public static function disponibilidadeDia(AgendaDataServico $data_agenda, Servico $servico) {

        // Pega o desconto caso tenha
        $desconto = $servico->descontoAtivo;

        // Configuracoes da agenda
        $configuracoes_agenda = $data_agenda->agendaServico->substituicoes_agenda;

        // Substituicoes agenda
        $substitui_net = $configuracoes_agenda[AgendaEnum::SUBSTITUI_NET] ?? null;
        $substitui_venda = $configuracoes_agenda[AgendaEnum::SUBSTITUI_VENDA] ?? null;

        // Busca se o produto tem alguma regra de antecedencia
        // Não é a antecedencia de quantos dias para frente pode vender e sim a que muda os valores
        $regra_antecedencia = ValorExcecaoDiaService::getRegraAtecedenciaServicoAtiva($servico);

        $variacaoes = [];
        $valor_venda_data = 0;

        // Calcula o valor de venda das variacoes
        foreach ($servico->variacaoServicoAtivas as $variacao) {

            // Valor net de cada variacao
            $net_variacao =  ($variacao->percentual / 100) * $data_agenda->valor_net;

            // Verifica se possui valores no NET para substituir
            if(is_array($substitui_net)) {
                $net_variacao = (string) number_format($net_variacao, 2, ".", "");
                $net_variacao = ($substitui_net[$net_variacao]) ?? $net_variacao;
            }

            // Verifica se há uma regra de antecedencia para valor diferenciado e aplica
            // Se não houver mantem o memso valor
            $net_variacao = ValorExcecaoDiaService::aplicarValorRegraAntecedencia($regra_antecedencia, $data_agenda->data, $net_variacao);

            // Valor de venda da variacao
            $venda_variacao = $net_variacao * $variacao->markup;

            // Verifica se o servico possui corretagem de valor
            if($servico->tipo_corretagem != ServicoEnum::SEM_CORRETAGEM && ($venda_variacao > 0)) {

                // Verifica se a corretagem é em percentual
                if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_PORCENTUAL) {
                    $venda_variacao += ($venda_variacao / 100 * $servico->corretagem);
                }

                // Verifica se a corretagem é em valor fixo
                if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_FIXA) {
                    $venda_variacao += $servico->corretagem;
                }
            }

            // Verifica se possui valores da venda para substituir
            if(is_array($substitui_venda)) {
                $venda_variacao = (string) number_format($venda_variacao, 2, ".", "");
                $venda_variacao = $substitui_venda[$venda_variacao] ?? $venda_variacao;
            }

            // Salva o maior valor de venda ou salva o valor da variacao destaque
            $valor_venda_data = (
                $venda_variacao > $valor_venda_data || $variacao->destaque == VariacaoServicoEnum::VARIACAO_DESTAQUE
            ) ? $venda_variacao : $valor_venda_data;

            // Caso a variacao deva ser vendida por 1 real
            if($variacao->percentual == 0 && $variacao->markup == AgendaEnum::MARKUP_UM_REAL) {
                $venda_variacao = 1;
            }

            // Guarda o valor sem desconto
            $valor_original = $venda_variacao;

            // Aplica o desconto caso tenha
            $venda_variacao = DescontoService::aplicarDescontoValor($desconto, $venda_variacao);

            // Dados para o array
            $variacaoes[] = [
                'variacao_id' => $variacao->id,
                'variacao' => $variacao->nome,
                'descricao' => $variacao->descricao,
                'bloqueio' => $variacao->consome_bloqueio,
                'valor_venda' => (float) number_format($venda_variacao, 2, ".", ""),
                'valor_venda_original' => (float) number_format($valor_original, 2, ".", ""),
                'valor_venda_brl' => formataValor($venda_variacao),
                'valor_venda_brl_original' => formataValor($valor_original),
            ];
        }

        // Obtem a variação com o valor de venda mais alto para exibir no calendario
        $valor_venda_mais_alto = 0;
        foreach($variacaoes as $variacao) {

            if($valor_venda_mais_alto < $variacao['valor_venda']) {
                $valor_venda_mais_alto = $variacao['valor_venda'];
            }
        }

        $data_agenda_valor_venda_original = $valor_venda_mais_alto;
        $valor_venda_data_original = $valor_venda_mais_alto;
        $data_agenda_valor_venda = $valor_venda_mais_alto;
        $valor_venda_data = $valor_venda_mais_alto;

        // Dados da agenda
        $retorno['disponibilidade'][] = [
            'data_servico_id' => $data_agenda->id,
            'data' => $data_agenda->data->format('Y-m-d'),
            'valor_venda' => (float) number_format($data_agenda_valor_venda, 2, ".", ""),
            'valor_venda_original' => (float) number_format($data_agenda_valor_venda_original, 2, ".", ""),
            'valor_venda_brl' => formataValor($valor_venda_data),
            'valor_venda_brl_original' => formataValor($valor_venda_data_original),
            'variacoes' => $variacaoes,
            'disponibilidade' => $data_agenda->disponivel
        ];

        // Array para o calendario
        $retorno['events'][] = [
            'date' => $data_agenda->data->format('Y-m-d') . " 00:00:00",
            'text' => "R$ " . formataValor($valor_venda_data),
            'text_original' => "R$ " . formataValor($valor_venda_data_original),
        ];




        return $retorno;
    }

    /**
     * Cadastrar datas na agenda
     *
     * @param $agenda
     * @param array $dados_lancamento
     * @return array
     */
    public static function storeDatasAgenda($agenda, array $dados_lancamento)
    {
        // Array com as linhas que ira inserir no banco
        $rows = [];

        // Data de inicio da agenda
        $date_start = Carbon::createFromFormat("d/m/Y H:i:s", $dados_lancamento['date_start'] . " 00:00:00");

        // Data de termino da agenda
        $date_end = Carbon::createFromFormat("d/m/Y H:i:s", $dados_lancamento['date_end'] . " 23:59:59");

        // Dias da semana para lançar
        $dias_semana = $dados_lancamento['dias_semana'];

        // Quantidade para lançar
        $quantidade = (int) $dados_lancamento['quantidade'];

        // Valor net do periodo
        $valor_net = str_replace(",", ".", str_replace(".", "", $dados_lancamento['valor_net']));

        // Recupera as datas do periodo informado
        $period = CarbonPeriod::create($date_start, $date_end);

        // Recupera as datas já cadastradas na agenda
        $has_agenda = AgendaDataServico::whereBetween('data', [$date_start, $date_end])
            ->where('agenda_servico_id', $agenda->id)->orderBy('data')->get();

        // Recupera o principal servico com a variacao mais cara
        $servico = AgendaServico::with(['servicos' => function($query) {
            return $query->with(['variacaoServico' => function ($q) {
                return $q->orderBy('destaque', 'ASC')->limit(1);
            }])->oldest()->limit(1);
        }])->find($agenda->id);

        // Variacao mais cara do servico principal da agenda
        $variacao_servico = $servico->servicos->first()->variacaoServico->first();

        // Valor net da variacao SOMENTE COMO REFERENCIA
        $net_variacao =  ($variacao_servico->percentual / 100) * $valor_net;

        // Valor de venda da variacao SOMENTE COMO REFERENCIA
        $venda_variacao = $net_variacao * $variacao_servico->markup;

        // Verifica se o servico possui corretagem de valor
        if($servico->tipo_corretagem != ServicoEnum::SEM_CORRETAGEM && ($venda_variacao > 0)) {

            // Verifica se a corretagem é em percentual
            if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_PORCENTUAL) {
                $venda_variacao += ($venda_variacao / 100 * $servico->corretagem);
            }

            // Verifica se a corretagem é em valor fixo
            if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_FIXA) {
                $venda_variacao += $servico->corretagem;
            }
        }

        // Monta o array com as datas para inserir no banco
        foreach ($period as $id => $date) {
            // Verifica se o dia da semana está selecionado
            if(in_array($date->dayOfWeekIso, $dias_semana)) {
                // Verifica se a data já está cadastrada
                $has_data = $has_agenda->first(function ($data) use ($date) {
                    return ($data->data == $date);
                });
                // Caso não encontre a data
                if(is_null($has_data)) {
                    $rows[] = [
                        'agenda_servico_id' => $agenda->id,
                        'data' => $date->format('Y-m-d'),
                        'valor_net' => $valor_net,
                        'valor_venda' => $venda_variacao,
                        'disponivel' => $quantidade,
                        'status' => AgendaEnum::ATIVO,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }
            }
        }

        // Atualiza o status da agenda
        $agenda->update(['status' => AgendaEnum::COM_DISPONIBILIDADE]);

        // Insere no banco de dados
        $created = AgendaDataServico::insert($rows);

        return ['agenda' => $created];
    }

    /**
     * Atualiza os dados da agenda
     *
     * @param $agenda
     * @param array $dados
     * @return array
     */
    public static function updateDatasAgenda($agenda, array $dados)
    {
        // Array com as linhas que ira atualizar no banco
        $ids_agenda = [];

        // Data de inicio da agenda
        $date_start = Carbon::createFromFormat("d/m/Y H:i:s", $dados['date_start'] . " 00:00:00");

        // Data de termino da agenda
        $date_end = Carbon::createFromFormat("d/m/Y H:i:s", $dados['date_end'] . " 23:59:59");

        // Dias da semana para atualizar
        $dias_semana = $dados['dias_semana'];

        // Quantidade para lançar
        $quantidade = (isset($dados['quantidade'])) ? (int) $dados['quantidade'] : null;

        // Valor net do periodo
        $valor_net = (isset($dados['valor_net'])) ? str_replace(",", ".", str_replace(".", "", $dados['valor_net'])) : null;

        // Recupera as datas já cadastradas na agenda
        $datas_agenda = AgendaDataServico::whereBetween('data', [$date_start, $date_end])
            ->where('agenda_servico_id', $agenda->id)->orderBy('data')->get();

        // Monta o array com as datas para atualizar
        foreach ($datas_agenda as $date_agenda) {
            // Verifica se o dia da semana está selecionado
            if(in_array($date_agenda->data->dayOfWeekIso, $dias_semana)) {
                $ids_agenda[] = $date_agenda->id;
            }
        }

        // Caso seja para atualizar a quantidade
        if(! is_null($quantidade)) {
            // Verifica a quantidade informada caso for maior que zero altera o status da agenda e da data
            if($quantidade > 0) {
                // Atualiza o status da agenda
                $agenda->update(['status' => AgendaEnum::COM_DISPONIBILIDADE]);
                // Status da data
                $status = AgendaEnum::ATIVO;
            } else {
                $status = AgendaEnum::INDISPONIVEL;
            }
            // Atualiza a quantidade nas datas
            $update = AgendaDataServico::whereIn('id', $ids_agenda)
                ->where('agenda_servico_id', $agenda->id)
                ->update(['disponivel' => $quantidade, 'status' => $status]);

            if($update) return ['update' => true, 'message' => 'Quantidade disponível atualizada!'];

            return ['update' => false, 'message' => 'Não foi possível atualizar a quantidade, tente novamente!'];
        }

        // Caso seja para atualizar o valor net
        if(! is_null($valor_net)) {

            // Recupera o principal servico com a variacao mais cara
            $servico = AgendaServico::with(['servicos' => function($query) {
                return $query->with(['variacaoServico' => function ($q) {
                    return $q->orderBy('percentual')->limit(1);
                }])->oldest()->limit(1);
            }])->find($agenda->id);

            // Variacao mais cara do servico principal da agenda
            $variacao_servico = $servico->servicos->first()->variacaoServico->first();

            // Valor net da variacao SOMENTE COMO REFERENCIA
            $net_variacao =  ($variacao_servico->percentual / 100) * $valor_net;

            // Valor de venda da variacao SOMENTE COMO REFERENCIA
            $valor_venda = $net_variacao * $variacao_servico->markup;

            // Verifica se o servico possui corretagem de valor
            if($servico->tipo_corretagem != ServicoEnum::SEM_CORRETAGEM && ($valor_venda > 0)) {

                // Verifica se a corretagem é em percentual
                if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_PORCENTUAL) {
                    $valor_venda += ($valor_venda / 100 * $servico->corretagem);
                }

                // Verifica se a corretagem é em valor fixo
                if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_FIXA) {
                    $valor_venda += $servico->corretagem;
                }
            }

            // Atualiza o valor net nas datas
            $update = AgendaDataServico::whereIn('id', $ids_agenda)
                ->where('agenda_servico_id', $agenda->id)
                ->update(['valor_venda' => $valor_venda, 'valor_net' => $valor_net]);

            if($update) return ['update' => true, 'message' => 'A tarifa net foi atualizada com sucesso!'];

            return ['update' => false, 'message' => 'Não foi possível atualizar a tarifa net, tente novamente!'];
        }

        return ['update' => false, 'message' => 'Não foi possível atualizar os dados da agenda, tente novamente!'];
    }

    /**
     * Remove datas agenda
     *
     * @param $agenda
     * @param array $dados
     * @return array
     */
    public static function removeDatasAgenda($agenda, array $dados)
    {
        // Array com as linhas que ira remover no banco
        $ids_agenda = [];

        // Data de inicio da agenda
        $date_start = Carbon::createFromFormat("d/m/Y H:i:s", $dados['date_start'] . " 00:00:00");

        // Data de termino da agenda
        $date_end = Carbon::createFromFormat("d/m/Y H:i:s", $dados['date_end'] . " 23:59:59");

        // Dias da semana para atualizar
        $dias_semana = $dados['dias_semana'];

        // Recupera as datas já cadastradas na agenda
        $datas_agenda = AgendaDataServico::whereBetween('data', [$date_start, $date_end])
            ->where('agenda_servico_id', $agenda->id)->orderBy('data')->get();

        // Monta o array com as datas para remover
        foreach ($datas_agenda as $date_agenda) {
            // Verifica se o dia da semana está selecionado
            if(in_array($date_agenda->data->dayOfWeekIso, $dias_semana)) {
                $ids_agenda[] = $date_agenda->id;
            }
        }

        // Atualiza a quantidade nas datas
        $remove = AgendaDataServico::whereIn('id', $ids_agenda)
            ->where('agenda_servico_id', $agenda->id)
            ->update(['disponivel' => 0, 'status' => AgendaEnum::INDISPONIVEL]);

        return ['remove' => $remove];
    }

    /**
     * Atualiza uma unica data
     *
     * @param array $dados
     * @return array
     */
    public static function atualizarDataAdmin(array $dados)
    {
        // Recupera os dados da agenda
        $data = AgendaDataServico::find($dados['data_id']);

        // Quantidade para lançar
        $quantidade = (int) $dados['quantidade'];

        // Valor net
        $valor_net = str_replace(",", ".", str_replace(".", "", $dados['valor_net']));

        // Caso colocou para desativar a data
        $status = ($quantidade == 0) ? AgendaEnum::INDISPONIVEL : AgendaEnum::ATIVO;

        // Recupera o principal servico com a variacao mais cara
        $servico = AgendaServico::with(['servicos' => function($query) {
            return $query->with(['variacaoServico' => function ($q) {
                return $q->orderBy('percentual')->limit(1);
            }])->oldest()->limit(1);
        }])->find($data->agenda_servico_id);

        // Variacao mais cara do servico principal da agenda
        $variacao_servico = $servico->servicos->first()->variacaoServico->first();

        // Valor net da variacao SOMENTE COMO REFERENCIA
        $net_variacao =  ($variacao_servico->percentual / 100) * $valor_net;

        // Valor de venda da variacao SOMENTE COMO REFERENCIA
        $valor_venda = $net_variacao * $variacao_servico->markup;

        // Verifica se o servico possui corretagem de valor
        if($servico->tipo_corretagem != ServicoEnum::SEM_CORRETAGEM && ($valor_venda > 0)) {

            // Verifica se a corretagem é em percentual
            if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_PORCENTUAL) {
                $valor_venda += ($valor_venda / 100 * $servico->corretagem);
            }

            // Verifica se a corretagem é em valor fixo
            if($servico->tipo_corretagem == ServicoEnum::CORRETAGEM_FIXA) {
                $valor_venda += $servico->corretagem;
            }
        }

        // Atualiza a data
        $update = $data->update([
            'disponivel' => $quantidade,
            'valor_net' => $valor_net,
            'valor_venda' => $valor_venda,
            'status' => $status
        ]);

        return ['update' => $update];
    }

    /**
     * Datas possível para alteração na reserva
     *
     * @param $reserva_id
     * @return array
     */
    public static function calendarioReservaAdmin($reserva_id)
    {
        // Recupera os dados da reserva
        $reserva = ReservaPedido::with('agendaDataServico')->find($reserva_id);

        // Data de hoje
        $today = Carbon::today();

        // Adiciona a antecedencia de venda para buscar a agenda
        $today->addDays($reserva->servico->antecedencia_venda)->startOfDay();

        // Recupera a agenda com as datas disponiveis
        $agenda = AgendaServico::with([
            'datasServico' => function ($q) use ($today, $reserva) {
                return $q->where('data', '>=', $today->format('Y-m-d'))
                    ->where('valor_net', $reserva->agendaDataServico->valor_net)
                    ->where('disponivel', '>=', $reserva->bloqueio_consumido)->where('status', AgendaEnum::ATIVO);
            }
        ])->find($reserva->agendaDataServico->agenda_servico_id);

        // Array com as datas disponiveis
        $retorno = [
            'data_atual' => $reserva->agendaDataServico->data->format('d/m/Y'),
            'quantidade' => $reserva->quantidade,
            'events' => [], 'disponibilidade' => []
        ];

        // Monta o array com as datas disponiveis
        foreach ($agenda->datasServico as $data_agenda) {

            // Se tem disponibilidade e o valor net é o mesmo que o comprado
            if ($data_agenda->disponivel >= $reserva->bloqueio_consumido && ($data_agenda->valor_net == $reserva->agendaDataServico->valor_net)) {

                // Dados da agenda
                $retorno['disponibilidade'][] = [
                    'data_servico_id' => $data_agenda->id,
                    'data' => $data_agenda->data->format('Y-m-d'),
                    'disponibilidade' => $data_agenda->disponivel
                ];

                // Array para o calendario
                $retorno['events'][] = ['date' => $data_agenda->data->format('Y-m-d') . " 00:00:00",];
            }
        }

        return $retorno;
    }
}
