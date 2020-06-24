<?php namespace TourFacil\Core\Services;

use TourFacil\Core\Models\Destino;

/**
 * Class CategoriaService
 * @package TourFacil\Core\Services
 */
class CategoriaService
{
    /**
     * Retorna os destinos com as categorias do canal de venda
     *
     * @param $canal_venda
     * @param bool $trashed
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Destino[]
     */
    public static function categoriasCanalVenda($canal_venda, $trashed = false)
    {
        return Destino::with(['categorias' => function($q) use ($trashed) {
            return ($trashed) ? $q->withTrashed() : "";
        }])->where('canal_venda_id', $canal_venda->id)->orderBy('nome', 'desc')->get();
    }
}
