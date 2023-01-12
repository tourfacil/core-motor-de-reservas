<?php

namespace TourFacil\Core\Services;
use TourFacil\Core\Enum\Faturas\StatusFaturaEnum;
use TourFacil\Core\Enum\Faturas\TipoFaturaEnum;
use TourFacil\Core\Enum\Faturas\TipoPeriodoFaturaEnum;
use TourFacil\Core\Models\Fatura;
use TourFacil\Core\Models\ReservaPedido;
use TourFacil\Core\Models\Fornecedor;
use Carbon\Carbon;

class FaturaService
{
    private $dia_fechamento_mensal = 1;

    private $dia_fechamento_semanal = 1;

    private $dia_fechamento_quinzenal_inicial = 1;

    private $dia_fechamento_quinzenal_final = 16;

    public function fecharFaturas()
    {
        $faturas = [];

        if(Carbon::today()->day == $this->dia_fechamento_mensal) {

            $faturas['mensais'] = $this->fecharFaturasMensais();

        }

        if(Carbon::today()->day == $this->dia_fechamento_quinzenal_inicial || Carbon::today()->day == $this->dia_fechamento_quinzenal_final) {

            $faturas['quinzenais'] = $this->fecharFaturasQuinzenais();

        }

        if(Carbon::today()->dayOfWeek == $this->dia_fechamento_semanal) {

            $faturas['semanais'] = $this->fecharFaturasSemanais();

        }
    }

    public function fecharFaturasMensais()
    {
        $fornecedores = Fornecedor::where('tipo_fatura', TipoFaturaEnum::MENSAL)->get();
        $faturas = [];

        foreach($fornecedores as $fornecedor) 
        {
            
            $reservas_e_datas = $this->getReservasParaFechamentoEDatas($fornecedor, TipoFaturaEnum::MENSAL, $fornecedor->tipo_periodo_fatura);
            $faturas[] = $this->criarFatura($fornecedor, $reservas_e_datas);

        }
        return $faturas;
    }

    public function fecharFaturasQuinzenais()
    {
        $fornecedores = Fornecedor::where('tipo_fatura', TipoFaturaEnum::QUINZENAL)->get();
        $faturas = [];

        foreach($fornecedores as $fornecedor) 
        {
            $reservas_e_datas = $this->getReservasParaFechamentoEDatas($fornecedor, TipoFaturaEnum::QUINZENAL, $fornecedor->tipo_periodo_fatura);
            $faturas[] = $this->criarFatura($fornecedor, $reservas_e_datas);
        }
        return $faturas;
    }

    public function fecharFaturasSemanais()
    {
        $fornecedores = Fornecedor::where('tipo_fatura', TipoFaturaEnum::SEMANAL)->get();
        $faturas = [];

        foreach($fornecedores as $fornecedor) 
        {

            $reservas_e_datas = $this->getReservasParaFechamentoEDatas($fornecedor, TipoFaturaEnum::SEMANAL, $fornecedor->tipo_periodo_fatura);
            $faturas[] = $this->criarFatura($fornecedor, $reservas_e_datas);

        }
        return $faturas;
    }

    private function getReservasParaFechamentoEDatas(Fornecedor $fornecedor, $tipo_fatura, $tipo_periodo)
    {
        $data_inicial = "";
        $data_final = "";

        $data_inicial_retorno = "";
        $data_final_retorno = "";

        switch($tipo_fatura) {

            case TipoFaturaEnum::MENSAL:
                $data_inicial = Carbon::today()->subDays(1)->startOfMonth();
                $data_final = Carbon::today()->subDays(1)->endOfMonth()->addDays(1);

                $data_inicial_retorno = Carbon::today()->subDays(1)->startOfMonth();
                $data_final_retorno = Carbon::today()->subDays(1)->endOfMonth();

                break;

            case TipoFaturaEnum::QUINZENAL:
                
                if(Carbon::today()->day == $this->dia_fechamento_quinzenal_inicial) {

                    $data_inicial = Carbon::today()->subDays(1)->day(16);
                    $data_final = Carbon::today()->subDays(1)->endOfMonth()->addDays(1);

                    $data_inicial_retorno = Carbon::today()->subDays(1)->day(16);
                    $data_final_retorno = Carbon::today()->subDays(1)->endOfMonth();

                } 
                else if(Carbon::today()->day == $this->dia_fechamento_quinzenal_final) {

                    $data_inicial = Carbon::today()->startOfMonth();
                    $data_final = Carbon::today()->day(15)->addDays(1);

                    $data_inicial_retorno = Carbon::today()->startOfMonth();
                    $data_final_retorno = Carbon::today()->day(15);

                }
                break;

            case TipoFaturaEnum::SEMANAL:

                $data_inicial = Carbon::today()->subDays(1)->startOfWeek();
                $data_final = Carbon::today()->subDays(1)->endOfWeek()->addDays(1);

                $data_inicial_retorno = Carbon::today()->subDays(1)->startOfWeek();
                $data_final_retorno = Carbon::today()->subDays(1)->endOfWeek();
                break;    
        }

        $reservas = ReservaPedido::whereNull('fatura_id')
        ->with('agendaDataServico')
        ->whereIn('status', ['ATIVA', 'UTILIZADO'])
        ->where('fornecedor_id', $fornecedor->id);

        if($tipo_periodo == TipoPeriodoFaturaEnum::VENDA){

            $reservas->where('created_at', '>=', $data_inicial);
            $reservas->where('created_at', '<=', $data_final);

        } else {

            $reservas->whereHas('agendaDataServico', function ($agenda) use ($data_inicial, $data_final) {
                $agenda->where('data', '>=', $data_inicial);
                $agenda->where('data', '<=', $data_final);
            });

        }

        return [
            'reservas' => $reservas->get(),
            'data_inicial' => $data_inicial_retorno,
            'data_final' => $data_final_retorno,
        ];
    }

    private function criarFatura(Fornecedor $fornecedor, $reservas_e_datas)
    {

        $data_inicial = $reservas_e_datas['data_inicial'];
        $data_final = $reservas_e_datas['data_final'];
        $data_pagamento = Carbon::today()->addDays(10);
        $valor = $reservas_e_datas['reservas']->sum('valor_net');
        $quantidade = $reservas_e_datas['reservas']->sum('quantidade');
        $quantidade_reservas = $reservas_e_datas['reservas']->count();

        $fatura = [
            'fornecedor_id' => $fornecedor->id,
            'inicio' => $data_inicial,
            'final' => $data_final,
            'data_pagamento' => $data_pagamento,
            'status' => StatusFaturaEnum::PENDENTE_APROVACAO,
            'tipo' => $fornecedor->tipo_fatura,
            'tipo_periodo' => $fornecedor->tipo_periodo_fatura,
            'valor' => $valor,
            'quantidade' => $quantidade,
            'quantidade_reservas' => $quantidade_reservas,
        ];

        $fatura = Fatura::create($fatura);

        return $fatura;
    }

    private function log($texto)
    {
        echo $texto;
    }
}
