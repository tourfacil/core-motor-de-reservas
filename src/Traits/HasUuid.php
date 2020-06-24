<?php namespace TourFacil\Core\Traits;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

/**
 * Trait HasUuid
 * @package TourFacil\Core\Traits
 */
trait HasUuid
{
    /**
     * Generate uuid on create model
     */
    protected static function bootHasUuid()
    {
        static::creating(function (Model $model) {
            $model->uuid = Uuid::uuid1()->toString();
        });
    }
}
