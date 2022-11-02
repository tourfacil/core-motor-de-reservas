<?php

namespace TourFacil\Core\Services\RegraServico;

use Carbon\Carbon;
use TourFacil\Core\Enum\RegraServicoEnum;
use TourFacil\Core\Enum\StatusEnum;
use TourFacil\Core\Models\RegraServico;
use TourFacil\Core\Models\Servico;

class ValorExcecaoDiaService
{
    /**
     * @var string
     */
    private static $VALOR_FIXO = "FIXO";
    /**
     * @var string
     */
    private static $VALOR_PERCENTUAL = "PERCENTUAL";

    /**
     * Método que calcula o valor do serviço com base na antecedencia
     * Ele recebe a regra e a data e faz tudo sozinho. Se identificar que precisa de alteração, ele altera,
     * se não, ele retorna o valor original
     *
     * @param RegraServico $regra
     * @param $data_utilizacao
     * @param $valor_atual
     * @return int|mixed
     */
    public static function aplicarValorRegraAntecedencia($regra, $data_utilizacao, $valor_atual) {

        // Retorna o valor original caso a regra seja null
        if($regra == null) {
            return $valor_atual;
        }

        // Pega as regras e data de hoje
        $regras = $regra->regras;
        $data_hoje = Carbon::today();

        // Caso o dia esteja dentro da antecedencia. Faz o calculo e retorna, se não, retorna o valor original
        if($data_hoje->addDays($regras['antecedencia'])->isAfter($data_utilizacao)) {

            return self::calcularValor($regra, $valor_atual);

        } else {

            return $valor_atual;

        }
    }

    /**
     * Método que calcula as antecedencias de forma automatica para os dados `events` método disponibilidadeSite() da agenda
     * Ele é otimizado, pois não roda toda a agenda, e sim, somente as primeiras que a regra preve
     * Caso a regra seja null, retorna o array sem alterações
     *
     * @param RegraServico $regra
     * @param array $datas
     * @return array
     */
    public static function aplicarValorRegraAntecedenciaArrayEvents($regra, Array $datas) {

        // Caso a regra seja null, só retorna o array
        if($regra == null) {
            return $datas;
        }

        // Pega as regras
        $regras = $regra->regras;

        // Faz um loop no número de dias de antecedencia que a regra preve e não em toda a agenda, por otimização...
        for($i = 0; $i < $regras['antecedencia']; $i++) {

            // Atualiza os valores da agenda
            $data_utilizacao = Carbon::parse($datas[$i]['date']);
            $valor = floatval(str_replace("R$ ", "", $datas[$i]['text']));
            $datas[$i]['text'] = "R$ " . formataValor(self::aplicarValorRegraAntecedencia($regra, $data_utilizacao, $valor));
        }

        // Retorna
        return $datas;
    }

    /**
     * Método que calcula as antecedencias de forma automatica para os dados `disponibilidade` método disponibilidadeSite() da agenda
     * Ele é otimizado, pois não roda toda a agenda, e sim, somente as primeiras que a regra preve
     * Caso a regra seja null, retorna o array sem alterações
     *
     * @param RegraServico $regra
     * @param array $datas
     * @return array
     */
    public static function aplicarValorRegraAntecedenciaArrayDisponibilidade($regra, Array $datas) {

        // Caso a regra seja null, retorna o array original
        if($regra == null) {
            return $datas;
        }

        // Pega as regras
        $regras = $regra->regras;

        // Roda os primeiros itens da agenda de acordo com a regra. Por otimização, não roda toda a agenda, só o necessário
        for($i = 0; $i < $regras['antecedencia']; $i++) {

            // Calcula os valores
            $data_utilizacao = Carbon::parse($datas[$i]['data']);
            $datas[$i]['valor_venda'] = self::aplicarValorRegraAntecedencia($regra, $data_utilizacao, $datas[$i]['valor_venda']);
            $datas[$i]['valor_venda_brl'] = formataValor(self::aplicarValorRegraAntecedencia($regra, $data_utilizacao, floatval($datas[$i]['valor_venda_brl'])));

            // Calcula os valores das variações
            $variacoes = $datas[$i]['variacoes'];
            foreach($variacoes as $key => $variacao) {

                $variacao['valor_venda'] = self::aplicarValorRegraAntecedencia($regra, $data_utilizacao, $variacao['valor_venda']);
                $variacao['valor_venda_brl'] = formataValor(self::aplicarValorRegraAntecedencia($regra, $data_utilizacao, floatVal($variacao['valor_venda_brl'])));
                $datas[$i]['variacoes'][$key] = $variacao;
            }
        }

        return $datas;
    }

    /**
     * Função que calcula o valor da antecedencia
     * Ele resolve se for fixo ou percentual
     *
     * @param RegraServico $regra
     * @param $valor
     * @return int|mixed
     */
    private static function calcularValor($regra, $valor) {

        if($regra->regras['tipo_valor_servico'] == self::$VALOR_FIXO) {

            return self::evitarValorMenorQueUm($valor + $regra->regras['valor']);

        } else if($regra->regras['tipo_valor_servico'] == self::$VALOR_PERCENTUAL) {

            return self::evitarValorMenorQueUm($valor + $regra->regras['valor']);

        } else {
            return $valor;
        }
    }

    /**
     * Retorna a regra ativa do serviço com a maior prioridade
     *
     * @param Servico $servico
     * @return mixed
     */
    public static function getRegraAtecedenciaServicoAtiva(Servico $servico) {
        return RegraServico::where('tipo_regra', RegraServicoEnum::VALOR_EXCECAO_DIA)
                     ->where('servico_id', $servico->id)
                     ->where('status', StatusEnum::ATIVA)
                     ->orderBy('prioridade')
                     ->get()
                     ->first();
    }

    /**
     * Evita valores menores que um para evitar BUGS no gateway do pagamento
     *
     * @param $valor
     * @return int|mixed
     */
    private static function evitarValorMenorQueUm($valor) {

        if($valor > 1) {
            return $valor;
        } else {
            return 1;
        }
    }
}
