<?php namespace TourFacil\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Markdown
 * @package TourFacil\Core\Facades
 */
class Markdown extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'markdown';
    }
}
