<?php

namespace TourFacil\Core\Services;

use Illuminate\Support\Arr;

abstract class ParcelamentoService
{

    /**
     * O calculo da porcentagem de juros e feita
     * 1 - Numero da parcela multiplica pelo juros percentual
     * 2 - O valor com o juros é (valor informado * percuntual da parcela / 100) + valor informado
     *
     * @param $valor
     * @param $juros_parcela
     * @param $qtd_parcelas_sem_juros
     * @param $max_parcelas
     * @param bool $strict
     * @return array
     */
    public static function calculaParcelas($valor, $juros_parcela, $qtd_parcelas_sem_juros, $max_parcelas, $strict = false){

        $valor_minimo_parcela = \TourFacil\Core\Enum\ParcelamentoEnum::VALOR_MINIMO_PARCELA;
        $except = $strict ? ['juros'] : [];
        // Qtd minima de parcelas independente do valor minimo da parcela
        // Caso a compra seja menos de 50 reias
        $min_parcela = 1;

        $parcelamento = [];

        // Monta o array com o máximo de parcela permitida
        for($parcela = 1; $parcela <= $max_parcelas; $parcela++){
            // Parcelamentos sem juros
            if($parcela <= $qtd_parcelas_sem_juros){
                // Valor da parcela
                $valor_parcela = (float) number_format($valor / $parcela, 2, ".", "");
                // Limite do valor da parcela
                if($valor_parcela < $valor_minimo_parcela && ($parcela > $min_parcela)) break;
                // Dados da parcela sem juros
                $parcelamento[$parcela] = Arr::except([
                    'parcela' => $parcela,
                    'valor_total' => (float) number_format($valor, 2, ".", ""),
                    'valor_juros' => 0,
                    'valor_parcela' => $valor_parcela,
                    'juros' => $juros_parcela
                ], $except);
            } else {
                // Calcula o valor percentual de juros da parcela
                // $percentual = $parcela * $juros_parcela;
                $percentual = config("site.juros_parcelas.$parcela");
                // Valor total com o juros da parcela
                $valor_com_juros = $valor * ($percentual / 100) + $valor;
                // Valor da parcela
                $valor_parcela = (float) number_format($valor_com_juros / $parcela, 2, ".", "");
                // Limite do valor da parcela
                if($valor_parcela < $valor_minimo_parcela) break;
                // Dados da parcela com juros
                $parcelamento[$parcela] = Arr::except([
                    'parcela' => $parcela,
                    'valor_total' => (float) number_format($valor_com_juros, 2, ".", ""),
                    'valor_juros' => ($valor_parcela * $parcela) - $valor,
                    'valor_parcela' => $valor_parcela,
                    'juros' => $percentual
                ], $except);
            }
        }

        return $parcelamento;
    }
}
