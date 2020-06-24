<?php namespace TourFacil\Core\Providers;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use League\CommonMark\Converter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use Webuni\CommonMark\AttributesExtension\AttributesExtension;

/**
 * Class MarkdownParseProvider
 * @package TourFacil\Core\Providers
 */
class MarkdownParseProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerEnvironment();
        $this->registerMarkdown();
    }

    /**
     * Register the environment class.
     *
     * @return void
     */
    protected function registerEnvironment()
    {
        $this->app->singleton('markdown.environment', function () {
            $environment = Environment::createCommonMarkEnvironment();
            $environment->addExtension(new AttributesExtension());
            return $environment;
        });

        $this->app->alias('markdown.environment', Environment::class);
    }
    /**
     * Register the markdowm class.
     *
     * @return void
     */
    protected function registerMarkdown()
    {
        $this->app->singleton('markdown', function (Container $app) {
            $environment = $app['markdown.environment'];
            return new Converter(new DocParser($environment), new HtmlRenderer($environment));
        });

        $this->app->alias('markdown', Converter::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'markdown.environment',
            'markdown'
        ];
    }
}
