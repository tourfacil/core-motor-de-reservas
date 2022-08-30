<?php

namespace TourFacil\Core\Services;

use TourFacil\Core\Enum\StatusAvaliacaoServicoEnum;
use TourFacil\Core\Models\AvaliacaoServico;
use TourFacil\Core\Models\Servico;

abstract class AvaliacaoService
{
    public static function getAvaliacoesAprovadas(Servico $servico, $limite = 2) {
        return AvaliacaoServico::where('servico_id', $servico->id)
                                ->where('status', StatusAvaliacaoServicoEnum::APROVADO)
                                ->orderBy('nota', 'desc')
                                ->limit($limite)
                                ->get();
    }

    public static function getNotaMedia($avaliacoes) {

        if($avaliacoes->count() == 0) return 0;

        $media = $avaliacoes->sum('nota') / $avaliacoes->count();

        if(is_int($media)) return $media;

        return number_format($media, 1);
    }

    public static function getQuantidadeTotalAvaliacoes(Servico $servico) {
        return self::getAvaliacoesAprovadas($servico, 1000)->count();
    }
}
