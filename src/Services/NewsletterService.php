<?php namespace TourFacil\Core\Services;

use TourFacil\Core\Models\Newsletter;

/**
 * Class NewsletterService
 * @package TourFacil\Core\Services
 */
class NewsletterService
{
    /**
     * Cadastrar email na newsletter
     *
     * @param $email
     * @param $canal_venda_id
     * @return bool
     */
    public static function store($email, $canal_venda_id)
    {
        // Verifica se ja existe o email
        $has = Newsletter::where([
            "canal_venda_id" => $canal_venda_id, "email" => $email,
        ])->first();

        // Caso nao possua
        if(is_null($has)) {

            // Cadastra o novo email
            $newsletter = Newsletter::create([
                "canal_venda_id" => $canal_venda_id, "email" => $email,
            ]);

            return is_object($newsletter);
        }

        return true;
    }
}
