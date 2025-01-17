<?php namespace TourFacil\Core\Enum;

/**
 * Class TipoHomeDestinoEnum
 * @package TourFacil\Core\Enum
 */
abstract class TipoHomeDestinoEnum {

    // Serviços lancados manual
    const MANUAL = "MANUAL";

    // Serviços mais vendidos
    const MAIS_VENDIDOS = "MAIS_VENDIDOS";

    // Ultimos servicos cadastrados
    const ULTIMOS_CADASTRADOS = "ULTIMOS_CADASTRADOS";

    // Servicos que ficam na home do site
    const DESTAQUES = "DESTAQUES";

    const TIPOS_HOME_DESTINO = [
        self::MANUAL => "Manual",
        self::DESTAQUES => "Destaques home",
        self::MAIS_VENDIDOS => "Mais vendidos",
        self::ULTIMOS_CADASTRADOS => "Últimos cadastrados",
    ];
}
