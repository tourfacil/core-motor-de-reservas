<?php namespace TourFacil\Core\Enum;

/**
 * Class CategoriasEnum
 * @package TourFacil\Core\Enum
 */
abstract class CategoriasEnum
{
    // ID das categorias que sao ofertas
    const CATEGORIAS_OFERTA = [
        11, 20
    ];

    const PROMOCIONAL = "PROMOCIONAL";
    const DESTAQUE = "DESTAQUE";
    const NORMAL = "NORMAL";

    // Tipos de categoria
    const TIPOS_CATEGORIA = [
        self::NORMAL => "Categoria normal",
        self::PROMOCIONAL => "Categoria promocional",
        self::DESTAQUE => "Categoria de destaques",
    ];

    // Tipo da categoria no servico
    const CATEGORIA_PADRAO = "SIM";
    const CATEGORIA_NORMAL = "NAO";

    const CATEGORIA_PADRAO_SERVICO = [
        self::CATEGORIA_PADRAO => "Categoria padrão",
        self::CATEGORIA_NORMAL => "Categoria secundária",
    ];
}
