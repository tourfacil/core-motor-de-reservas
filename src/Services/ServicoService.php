<?php namespace TourFacil\Core\Services;

use Cache;
use DB;
use TourFacil\Core\Enum\AgendaEnum;
use TourFacil\Core\Enum\CategoriasEnum;
use TourFacil\Core\Enum\FotoServicoEnum;
use TourFacil\Core\Enum\ServicoEnum;
use TourFacil\Core\Enum\StatusReservaEnum;
use TourFacil\Core\Models\Servico;
use TourFacil\Core\Services\Cache\DefaultCacheService;

/**
 * Class ServicoService
 * @package TourFacil\Core\Services
 */
class ServicoService extends DefaultCacheService
{
    /**
     * Prefixo do cache
     *
     * @var string
     */
    protected static $prefix_cache = "servicos_";

    /**
     * Tag default para o cache
     *
     * @var string
     */
    protected static $tag = "servicos_";

    /**
     * Valida os dados do servico
     *
     * @param $servico
     * @return array
     */
    public static function verificarInfoServico($servico)
    {
        $retorno = ['alertas' => []];

        // Procura a categoria padrao do servico
        $categoria_padrao = $servico->categorias->first(function ($categoria) {
            return ($categoria->pivot->padrao == CategoriasEnum::CATEGORIA_PADRAO);
        });

        // Caso o servico esteja sem categoria
        if(is_null($categoria_padrao)) {
            $retorno['alertas'][] = "O serviço não possui nenhuma categoria padrão.";
        } elseif($servico->categorias->count() == 0) {
            $retorno['alertas'][] = "O serviço não possui nenhuma categoria.";
        }

        // Caso nao possua fotos
        if($servico->fotos->count() == 0) {
            $retorno['alertas'][] = "O serviço não possui nenhuma foto.";
        } else {
            // Recupera a foto principal
            $foto_principal = $servico->fotos->first(function ($foto) {
                return ($foto->tipo == FotoServicoEnum::PRINCIPAL);
            });

            // Caso ainda nao possua foto principal
            if(is_null($foto_principal)) {
                $retorno['alertas'][] = "É necessário definir uma foto como destaque.";
            }
        }

        // Verifica se possui agenda
        if(is_null($servico->agenda)) {
            $retorno['alertas'][] = "O serviço não possui agenda cadastrada.";
        } elseif($servico->agenda->status == AgendaEnum::SEM_DISPONIBILIDADE) {
            $retorno['alertas'][] = "A agenda deste serviço não possui datas disponíveis para compra.";
        }

        // Caso nao possua variacao
        if($servico->variacaoServico->count() == 0) {
            $retorno['alertas'][] = "O serviço não possui nenhuma variação cadastrada.";
        }

        return $retorno;
    }

    /**
     * Servicos mais vendidos do canal de venda
     *
     * @param $canal_venda_id
     * @param int $limit
     * @return mixed
     */
    public static function servicosMaisVendidos($canal_venda_id, $limit = 20)
    {
        return Cache::remember("mais_vendidos_{$canal_venda_id}_{$limit}", now()->addMinutes(30), function () use ($canal_venda_id, $limit) {
            return Servico::select("nome", DB::raw("SUM(reserva_pedidos.quantidade) AS `vendas`"))
                ->leftJoin('reserva_pedidos', 'reserva_pedidos.servico_id', '=', 'servicos.id')
                ->groupBy('servicos.nome')->where(['canal_venda_id' => $canal_venda_id, 'servicos.status' => ServicoEnum::ATIVO])
                ->whereIn('reserva_pedidos.status', [StatusReservaEnum::ATIVA, StatusReservaEnum::FINALIZAR, StatusReservaEnum::UTILIZADO])
                ->orderBy('vendas', 'DESC')->limit($limit)->get();
        });
    }

    /**
     * Retorna a lista da quantidade vendida de cada servico
     * Filtro opcional periodo de venda
     *
     * @param null $start
     * @param null $end
     * @return mixed
     */
    public static function rankingServicos($start = null, $end = null)
    {
        $query = Servico::select("servicos.id", "servicos.nome", DB::raw("SUM(reserva_pedidos.quantidade) AS `vendas`"))
            ->leftJoin('reserva_pedidos', 'reserva_pedidos.servico_id', '=', 'servicos.id')
            ->whereIn('reserva_pedidos.status', [StatusReservaEnum::ATIVA, StatusReservaEnum::FINALIZAR, StatusReservaEnum::UTILIZADO]);

        // Caso haja filtro por periodo
        if(! is_null($start) && ! is_null($end)){
            $query->whereBetween('reserva_pedidos.created_at', [$start, $end]);
        }

        return $query->groupBy('servicos.id', 'servicos.nome')->orderBy('vendas', 'DESC')->get();
    }

    /**
     * Pesquisa do servico para sites
     *
     * https://freek.dev/1182-searching-models-using-a-where-like-query-in-laravel
     *
     * @param $query
     * @return mixed
     * @throws \Exception
     */
    public static function pesquisarServicos($query)
    {
        // Recupera o canal id no env
        $canal_id = self::getCanalVenda();

        return Servico::with([
            'fotoPrincipal',
            'secoesCategoria',
            'categoria',
            'ranking' => function($q) {
                return $q->select(['servico_id', 'ranking']);
            },
            'destino' => function($q) {
                return $q->select(['id', 'slug', 'nome']);
            },
        ])->whereHas('destino', function ($q) {
            return $q->whereNull('deleted_at');
        })->where([
            'canal_venda_id' => $canal_id,
            'status' => ServicoEnum::ATIVO
        ])->whereLike(['palavras_chaves', 'servicos.nome'], mb_strtolower($query))->get([
            'id', 'slug', 'uuid', 'nome', 'valor_venda', 'cidade', 'destino_id'
        ]);
    }
}
