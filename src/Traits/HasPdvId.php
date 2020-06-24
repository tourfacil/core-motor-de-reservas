<?php namespace TourFacil\Core\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasPdvId
 * @package TourFacil\Core\Traits
 */
trait HasPdvId
{
    /**
     * Generate uuid on create model
     */
    protected static function bootHasPdvId()
    {
        static::creating(function (Model $model) {
            $model->pdv_id = substr($model->identificacao, 1, strlen($model->identificacao));
        });
    }
}
