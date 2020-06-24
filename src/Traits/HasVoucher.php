<?php namespace TourFacil\Core\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasVoucher
 * @package TourFacil\Core\Traits
 */
trait HasVoucher
{
    /**
     * Generate slug on create model and update
     */
    protected static function bootHasVoucher()
    {
        static::creating(function (Model $model) {

            // tamanho maximo do voucher
            $length = 8;

            // Numeros restantes que faltam para ser randomicos
            $restante = $length - strlen($model->pedido_id);

            // Prefixo do voucher é o ID do pedido
            $random_number = $model->pedido_id;

            // Monta o random number com o valores faltantes
            for ($i = 0; $i < $restante; $i++) {
                $random_number .= mt_rand(0,9);
            }

            $model->voucher = $random_number;
        });
    }
}
