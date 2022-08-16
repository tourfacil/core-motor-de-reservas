<?php

namespace TourFacil\Core\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Models\ReservaPedido;

abstract class AfiliadoService
{
    public static function getComissaoAfiliado(ReservaPedido  $reserva) {

        if($reserva->afiliado_id == null) {
            return 0;
        }

        $afiliado = $reserva->afiliado;

        switch ($reserva->servico->categorias->first()->nome) {

            case 'Passeios':
                return self::regraTres($reserva->valor_total, $afiliado->comissao_passeios);

            case 'Ingressos':
                return self::regraTres($reserva->valor_total, $afiliado->comissao_ingressos);

            case 'Gastronomia':
                return self::regraTres($reserva->valor_total, $afiliado->comissao_gastronomia);

            case 'Transfer':
                return self::regraTres($reserva->valor_total, $afiliado->comissao_transfer);

            default:
                return self::regraTres($reserva->valor_total, $afiliado->comissao_ingressos);
        }
    }

    public static function getComissaoPercentual(ReservaPedido $reserva) {

        if($reserva->afiliado_id == null) {
            return 0;
        }

        $afiliado = $reserva->afiliado;

        switch ($reserva->servico->categorias->first()->nome) {

            case 'Passeios':
               return $afiliado->comissao_passeios;

            case 'Gastronomia':
                return $afiliado->comissao_gastronomia;

            case 'Transfer':
                return $afiliado->comissao_transfer;

            default:
                return $afiliado->comissao_ingressos;
        }
    }

    private static function regraTres($valor, $porcentagem) {
        $porcentagem = str_replace(",", '.', $porcentagem);
        return ($valor * $porcentagem) / 100;
    }

    public static function relatorioAfiliados(Request $request) {

        // Array para guardar os dados necessários
        $dados = [];

        // Guarda o tipo de data vindo da requisição e caso não seja informado, seta como VENDA
        $dados['tipo_data'] = $request->get('tipo_operacao') ?? 'VENDA';

        if(is_null($request->get('inicio')) || is_null($request->get('final'))) {
            $dados['inicio'] = Carbon::now()->startOfMonth();
            $dados['final'] = Carbon::now()->endOfMonth();
        } else {
            $dados['inicio'] = Carbon::parse($request->get('inicio'));
            $dados['final'] = Carbon::parse($request->get('final'));
        }

        // Inicia a query buscando reservas não nullas e onde o status esteja válido
        $reservas = ReservaPedido::whereNotNull('afiliado_id')
            ->whereIn('status', StatusReservaEnum::RESERVAS_VALIDAS);

        // Busca as reservas por VENDA ou UTILIZACAO
        if($dados['tipo_data'] == 'UTILIZACAO') {
            $reservas->whereHas('agendaDataServico', function($query) use ($dados) {
                $query->whereDate('data', '>=', $dados['inicio']);
                $query->whereDate('data', '<=', $dados['final']);
            });
        } else {
            $reservas->whereDate('created_at', '>=', $dados['inicio']);
            $reservas->whereDate('created_at', '<=', $dados['final']);
        }

        // Carrega as relações de agenda e afiliado
        $reservas = $reservas->with([
            'agendaDataServico',
            'afiliado'
        ]);

        // Busca as reservas
        $reservas = $reservas->get();

        $dados['total_vendido'] = 0;
        $dados['total_comissionado'] = 0;
        $dados['total_quantidade'] = 0;
        $dados['afiliados'] = [];

        foreach($reservas as $reserva) {
            $comissao_afiliado = AfiliadoService::getComissaoAfiliado($reserva);

            $dados['total_vendido'] += $reserva->valor_total;
            $dados['total_comissionado'] += $comissao_afiliado;
            $dados['total_quantidade'] += $reserva->quantidade;

            if(!array_key_exists($reserva->afiliado->nome_fantasia, $dados['afiliados'])) {
                $dados['afiliados'][$reserva->afiliado->nome_fantasia]['valor_venda'] = $reserva->valor_total;
                $dados['afiliados'][$reserva->afiliado->nome_fantasia]['valor_comissao'] = $comissao_afiliado;
                $dados['afiliados'][$reserva->afiliado->nome_fantasia]['quantidade'] = $reserva->quantidade;
                $dados['afiliados'][$reserva->afiliado->nome_fantasia]['afiliado'] = $reserva->afiliado;
            } else {
                $dados['afiliados'][$reserva->afiliado->nome_fantasia]['valor_venda'] += $reserva->valor_total;
                $dados['afiliados'][$reserva->afiliado->nome_fantasia]['valor_comissao'] += $comissao_afiliado;
                $dados['afiliados'][$reserva->afiliado->nome_fantasia]['quantidade'] += $reserva->quantidade;
            }
        }

        return $dados;
    }
}
