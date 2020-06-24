<?php namespace TourFacil\Core\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait HasSlug
 * @package TourFacil\Core\Traits
 */
trait HasSlug
{
    /**
     * Default key slug
     * @var string
     */
    protected $key_slug = "nome";

    /**
     * Generate slug on create model and update
     */
    protected static function bootHasSlug()
    {
        static::creating(function (Model $model) {
            $model->slug = str_slug($model->{$model->key_slug});
        });

        static::updating(function (Model $model) {
            $model->slug = str_slug($model->{$model->key_slug});
        });
    }
}
