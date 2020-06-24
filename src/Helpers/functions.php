<?php

if(! function_exists('markdown')) {

    /**
     * @param $markdown
     * @return mixed
     */
    function markdown($markdown) {
        return TourFacil\Core\Markdown\MarkdownParser::parser($markdown);
    }
}

if(! function_exists('markdown_cache')) {

    /**
     * @param $markdown
     * @param $key
     * @param bool $cache
     * @return mixed
     */
    function markdown_cache($markdown, $key, $cache = true) {
        return TourFacil\Core\Markdown\MarkdownParser::parserCache($markdown, $key, $cache);
    }
}
