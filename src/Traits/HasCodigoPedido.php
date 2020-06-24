<?php namespace TourFacil\Core\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasCodigoPedido
 * @package TourFacil\Core\Traits
 */
trait HasCodigoPedido
{
    /**
     * Generate uuid on create model
     */
    protected static function bootHasCodigopedido()
    {
        static::creating(function (Model $model) {

            // tamanho maximo do codigo
            $length = $model->codigo_length ?? 8;

            // Numeros restantes que faltam para ser randomicos
            $restante = $length - strlen($model->cliente_id);

            // Prefixo do voucher é o ID do pedido
            $random_number = $model->cliente_id;

            // Monta o random number com o valores faltantes
            for ($i = 0; $i < $restante; $i++) {
                $random_number .= mt_rand(0,9);
            }

            $model->codigo = $random_number;
        });
    }
}
