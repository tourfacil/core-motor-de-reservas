<?php

namespace TourFacil\Core\Services;

use App\Mail\AvaliacaoServicoMail;
use Carbon\Carbon;
use TourFacil\Core\Enum\StatusAvaliacaoServicoEnum;
use TourFacil\Core\Models\AvaliacaoServico;
use TourFacil\Core\Models\Pedido;
use TourFacil\Core\Models\ReservaPedido;
use TourFacil\Core\Models\Servico;

class AvaliacaoService
{
    public static function getAvaliacoesAprovadas(Servico $servico, $limite = 2) {
        return AvaliacaoServico::where('servico_id', $servico->id)
                                ->where('status', StatusAvaliacaoServicoEnum::APROVADO)
                                ->orderBy('nota', 'desc')
                                ->limit($limite)
                                ->get();
    }

    public static function getNotaMedia($servico) {

        $avaliacoes = self::getAvaliacoesAprovadas($servico, 100);

        if($avaliacoes->count() == 0) return 0;

        $media = $avaliacoes->sum('nota') / $avaliacoes->count();

        if(is_int($media)) return $media;

        return number_format($media, 1);
    }

    public static function getQuantidadeTotalAvaliacoes(Servico $servico) {
        return self::getAvaliacoesAprovadas($servico, 1000)->count();
    }

    public function dispararEmailsAvaliacao() {

        $this->log("Iniciando envio de e-mails para avaliação de clientes");

        $pedidos = Pedido::where('email_avaliacao', 'NAO_ENVIADO')
                         ->with(['reservas'])
                         ->whereHas('reservas', function($query) {
                             $query->whereHas('agendaDataServico', function($query2) {
                                 $query2->whereDate('data', '<', Carbon::today()->subDays(5));
                             });
                         })
                        ->get();

        $this->log($pedidos->count() . ' aptos para envio do e-mail');

        foreach($pedidos as $pedido) {

            (new AvaliacaoServicoMail())->send();

        }
    }

    private function log($texto) {
        echo $texto . "\n";
    }
}
