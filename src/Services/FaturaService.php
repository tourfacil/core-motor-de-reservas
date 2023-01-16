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
    private $dia_fechamento_mensal = 12;

    private $dia_fechamento_semanal = 1;

    private $dia_fechamento_quinzenal_inicial = 1;

    private $dia_fechamento_quinzenal_final = 16;

    private $status_inicial = StatusFaturaEnum::PENDENTE_PAGAMENTO;

    private $dias_prazo_pagamento = 10;

    public function fecharFaturas()
    {

        $this->log("Iniciando fechamento de faturas\n");

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

        $cont = 0;

        foreach($faturas as $tipo_fatura => $fatura) {

            foreach($fatura as $fatura_o) {
                $cont++;
            }
        }  

        $texto = Carbon::today()->format('d/m/Y H:i') . ': ' . $cont . " fatura(s) gerada(s)";

        simpleMail("Fechamento de faturas - Tour Fácil", $texto, config('site.email_alertas'));

        $this->log("Finalizado o fechamento de {$cont} fatura(s)\n");

    }

    private function fecharFaturasMensais()
    {
        $fornecedores = Fornecedor::where('tipo_fatura', TipoFaturaEnum::MENSAL)
        ->whereNotNull('tipo_periodo_fatura')
        ->get();
        $faturas = [];

        foreach($fornecedores as $fornecedor) 
        {
            
            $reservas_e_datas = $this->getReservasParaFechamentoEDatas($fornecedor, TipoFaturaEnum::MENSAL, $fornecedor->tipo_periodo_fatura);
            $faturas[] = $this->criarFatura($fornecedor, $reservas_e_datas);

        }
        return $faturas;
    }

    private function fecharFaturasQuinzenais()
    {
        $fornecedores = Fornecedor::where('tipo_fatura', TipoFaturaEnum::QUINZENAL)
        ->whereNotNull('tipo_periodo_fatura')
        ->get();
        $faturas = [];

        foreach($fornecedores as $fornecedor) 
        {
            $reservas_e_datas = $this->getReservasParaFechamentoEDatas($fornecedor, TipoFaturaEnum::QUINZENAL, $fornecedor->tipo_periodo_fatura);
            $faturas[] = $this->criarFatura($fornecedor, $reservas_e_datas);
        }
        return $faturas;
    }

    private function fecharFaturasSemanais()
    {
        $fornecedores = Fornecedor::where('tipo_fatura', TipoFaturaEnum::SEMANAL)
        ->whereNotNull('tipo_periodo_fatura')
        ->get();
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
                $data_final = Carbon::today()->subDays(1)->endOfMonth();

                $data_inicial_retorno = Carbon::today()->subDays(1)->startOfMonth();
                $data_final_retorno = Carbon::today()->subDays(1)->endOfMonth();

                break;

            case TipoFaturaEnum::QUINZENAL:
                
                if(Carbon::today()->day == $this->dia_fechamento_quinzenal_inicial) {

                    $data_inicial = Carbon::today()->subDays(1)->day(16);
                    $data_final = Carbon::today()->subDays(1)->endOfMonth();

                    $data_inicial_retorno = Carbon::today()->subDays(1)->day(16);
                    $data_final_retorno = Carbon::today()->subDays(1)->endOfMonth();

                } 
                else if(Carbon::today()->day == $this->dia_fechamento_quinzenal_final) {

                    $data_inicial = Carbon::today()->startOfMonth();
                    $data_final = Carbon::today()->day(15);

                    $data_inicial_retorno = Carbon::today()->startOfMonth();
                    $data_final_retorno = Carbon::today()->day(15);

                }
                break;

            case TipoFaturaEnum::SEMANAL:

                $data_inicial = Carbon::today()->subDays(1)->startOfWeek();
                $data_final = Carbon::today()->subDays(1)->endOfWeek();

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
            $reservas->where('created_at', '<=', $data_final->addDays(1));

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
        $data_pagamento = Carbon::today()->addDays($this->dias_prazo_pagamento);
        $valor = $reservas_e_datas['reservas']->sum('valor_net');
        $quantidade = $reservas_e_datas['reservas']->sum('quantidade');
        $quantidade_reservas = $reservas_e_datas['reservas']->count();

        $fatura = [
            'fornecedor_id' => $fornecedor->id,
            'inicio' => $data_inicial,
            'final' => $data_final,
            'data_pagamento' => $data_pagamento,
            'status' => $this->status_inicial,
            'tipo' => $fornecedor->tipo_fatura,
            'tipo_periodo' => $fornecedor->tipo_periodo_fatura,
            'valor' => $valor,
            'quantidade' => $quantidade,
            'quantidade_reservas' => $quantidade_reservas,
        ];

        $fatura = Fatura::create($fatura);

        foreach($reservas_e_datas['reservas'] as $reserva) {
            $reserva->update(['fatura_id' => $fatura->id]);
        }

        $this->log("Fatura de {$fatura->fornecedor->nome_fantasia} gerada - ID: {$fatura->id} \n");

        return $fatura;
    }

    private function log($texto)
    {
        echo $texto;
    }
}
