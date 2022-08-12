<?php namespace TourFacil\Core\Services;

use TourFacil\Core\Enum\IntegracaoEnum;
use TourFacil\Core\Models\ReservaPedido;
use TourFacil\Core\Services\Integracao\NovaXS\Snowland\SnowlandService;

/**
 * Class IntegracoesService
 * @package TourFacil\Core\Services
 */
class IntegracoesService
{
    /**
     * Gera os vouchers de integração
     *
     * @param $pedido
     * @throws \Exception
     */
    public static function gerarVouchers($pedido)
    {
        // Recupera as reservas do pedido
        $reservas = ReservaPedido::with('servico')
            ->where('pedido_id', $pedido->id)->get();

        // Percorre cada reserva para verificar se tem integração com algum parque
        foreach ($reservas as $reserva) {
            // Gera o voucher do Snowland
            if($reserva->servico->integracao == IntegracaoEnum::SNOWLAND) {
                self::snowland($reserva);
            }
        }
    }

    /**
     * Voucher do Snowland
     *
     * @param $reserva
     * @throws \Exception
     */
    private static function snowland($reserva)
    {
        // Recupera os dados necessarios para gerar o voucher do snowland
        $reserva = ReservaPedido::with([
            'servico',
            'pedido.cliente',
            'agendaDataServico',
            'quantidadeReserva.variacaoServico',
            'dadoClienteReservaPedido',
        ])->find($reserva->id);

        (new SnowlandService($reserva))->gerarVoucherSnowland();
    }
}
