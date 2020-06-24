<?php namespace TourFacil\Core\Services\Relatorios;

use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Models\ReservaPedido;

/**
 * Class ReservasAutenticadaService
 * @package TourFacil\Core\Services\Relatorios
 */
class ReservasAutenticadaService
{
    /**
     * Reservas autenticadas por fornecedor e periodo
     * Adicional servicos
     *
     * @param $fornecedor_id
     * @param $inicio
     * @param $final
     * @param $canal_venda_id
     * @param $servicos
     * @param array $relacoes
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|ReservaPedido[]
     */
    public static function relatorioAutenticadoFornecedor($fornecedor_id, $inicio, $final, $canal_venda_id, $servicos = null, $relacoes = [])
    {
        $relacoes = array_merge(['servico', 'agendaDataServico', 'validacao'], $relacoes);

        // Pesquisa das reservas autenticadas
        $query = ReservaPedido::with($relacoes)
            ->whereHas('validacao', function ($q) use ($inicio, $final) {
            return $q->whereBetween('validado', [$inicio, $final]);
        })->whereHas('pedido', function ($q) use ($canal_venda_id) {
            return $q->where('canal_venda_id', $canal_venda_id);
        })->where([
            'fornecedor_id' => $fornecedor_id,
            'status' => StatusReservaEnum::UTILIZADO
        ]);

        // Filtra por servicos
        if(is_array($servicos)) {
            $query->whereIn('servico_id', $servicos);
        }

        return $query->get();
    }
}
