<?php namespace TourFacil\Core\Services\Pagamento\Getnet\Traits;

/**
 * Trait Funcoes
 * @package TourFacil\Core\Services\Pagamento\Getnet\Traits
 */
trait Funcoes
{
    /**
     * Retorna somente os numeros de uma string
     *
     * @param string $string
     * @return string|string[]|null
     */
    protected function onlyNumbers(string $string)
    {
        return preg_replace("/[^0-9]/", "", $string);
    }

    /**
     * Remove os acentos de uma string
     *
     * @param string $string
     * @return string|string[]|null
     */
    protected function removeAccentuation(string $string)
    {
        $string = preg_replace('/[áàãâä]/ui', 'a', $string);
        $string = preg_replace('/[éèêë]/ui', 'e', $string);
        $string = preg_replace('/[íìîï]/ui', 'i', $string);
        $string = preg_replace('/[óòõôö]/ui', 'o', $string);
        $string = preg_replace('/[úùûü]/ui', 'u', $string);
        $string = preg_replace('/[ç]/ui', 'c', $string);

        return $string;
    }

    /**
     * Transforma real para centavo
     *
     * @param string $valor
     * @return int
     */
    protected function toCent(string $valor)
    {
        return (int) number_format($valor * 100, 0, "", "");
    }
}
