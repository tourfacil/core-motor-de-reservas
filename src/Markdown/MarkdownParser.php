<?php namespace TourFacil\Core\Markdown;

use TourFacil\Core\Facades\Markdown;
use TourFacil\Core\Services\Cache\DefaultCacheService;

/**
 * Class MarkdownParser
 * @package TourFacil\Core\Markdown
 */
class MarkdownParser extends DefaultCacheService
{
    /**
     * Prefix cache
     *
     * @var string
     */
    protected static $prefix_cache = "markdown_";

    /**
     * @var string
     */
    protected static $tag = "markdown_";

    /**
     * Parse markdown
     *
     * @param $markdown
     * @return string
     */
    public static function parser($markdown)
    {
        return Markdown::convertToHtml($markdown);
    }

    /**
     * Parse markdown with cache
     *
     * @param $markdown
     * @param $key
     * @param bool $cache
     * @return mixed
     */
    public static function parserCache($markdown, $key, $cache = true)
    {
        return self::run($cache, __FUNCTION__ . $key, function () use ($markdown) {
            return Markdown::convertToHtml($markdown);
        });
    }
}
