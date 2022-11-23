<?php

namespace TourFacil\Core\Services;

use App\Mail\AvaliacaoServicoMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use TourFacil\Core\Enum\StatusAvaliacaoServicoEnum;
use TourFacil\Core\Enum\StatusEmailAvaliacaoEnum;
use TourFacil\Core\Models\AvaliacaoServico;
use TourFacil\Core\Models\Pedido;
use TourFacil\Core\Models\PedidoAvaliacaoMailHashLogin;
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
        $relatorio = "";

        $pedidos = Pedido::where('email_avaliacao', StatusEmailAvaliacaoEnum::NAO_ENVIADO)
                         ->whereIn('status', ['PAGO', 'UTILIZADO'])
                         ->with(['reservas'])
                         ->whereHas('reservas', function($query) {
                             $query->whereHas('agendaDataServico', function($query2) {
                                 $query2->whereDate('data', '<', Carbon::today()->subDays(5));
                             });
                         })
                        ->get();

        $this->log($pedidos->count() . ' aptos para envio do e-mail');

        foreach($pedidos as $key => $pedido) {

            $this->log("Enviando e-mail de avaliação para o cliente " . $pedido->cliente->nome);

            if($pedido->pedidoAvaliacaoMailHashLogin == null) {

                $uuid = Str::uuid();
                $hash = \Hash::make(Str::uuid());

                $uuid = $this->removerCaracteresUUIDHash($uuid);
                $hash = $this->removerCaracteresUUIDHash($hash);

                PedidoAvaliacaoMailHashLogin::create([
                    'pedido_id' => $pedido->id,
                    'uuid' => $uuid,
                    'hash' => $hash
                ]);
            }

            Mail::to($pedido->cliente->email)->send(new AvaliacaoServicoMail($pedido));

            $pedido->update([
                'email_avaliacao' => StatusEmailAvaliacaoEnum::ENVIADO
            ]);

            $relatorio .= "#{$pedido->codigo} - {$pedido->created_at->format('d/m/Y')} - {$pedido->cliente->nome} - {$pedido->cliente->email} \n";

            sleep(1);
        }

        simpleMail(
        "E-mails de avaliação disparados",
        "Foram disparados " .  $pedidos->count() . " emails solicitando avaliação.\n{$relatorio}",
       "dev@tourfacil.com.br"
    );

    }

    private function removerCaracteresUUIDHash(String $codigo) {
        $codigo = str_replace("/", "b", $codigo);
        $codigo = str_replace("&", "e", $codigo);
        $codigo = str_replace("?", "i", $codigo);
        return $codigo;
    }

    private function log($texto) {
        echo $texto . "\n";
    }
}
