<?php namespace TourFacil\Core\Services;

use TourFacil\Core\Models\Destino;

class DestinoService
{
    /**
     * Destinos por canal de venda
     *
     * @param $canal_venda
     * @param bool $trashed
     * @return mixed
     */
    public static function destinoCanalVenda($canal_venda, $trashed = false)
    {
        $destinos = Destino::where('canal_venda_id', $canal_venda->id);

        if($trashed) $destinos->withTrashed();

        return $destinos->orderBy('created_at', 'desc')->get();
    }

    /**
     * Destinos que possuem categorias ativas
     *
     * @param $canal_venda
     * @param bool $trashed
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Destino[]
     */
    public static function destinosHasCategorias($canal_venda, $trashed = false)
    {
        $destinos = Destino::with('categorias')->whereHas('categorias')
            ->where('canal_venda_id', $canal_venda->id);

        if($trashed) $destinos->withTrashed();

        return $destinos->orderBy('created_at', 'desc')->get();
    }


}
