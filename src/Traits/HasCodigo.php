<?php namespace TourFacil\Core\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasCodigo
 * @package TourFacil\Core\Traits
 */
trait HasCodigo
{
    /**
     * Generate uuid on create model
     */
    protected static function bootHasCodigo()
    {
        static::creating(function (Model $model) {
            $length = $model->codigo_length ?? 6;
            $model->codigo = strtoupper(str_random($length));
        });
    }
}
