<?php

namespace ScoutEngines\Postgres;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\Builder;
use Laravel\Scout\EngineManager;
use ScoutEngines\Postgres\TsQuery\PhraseToTsQuery;
use ScoutEngines\Postgres\TsQuery\PlainToTsQuery;
use ScoutEngines\Postgres\TsQuery\ToTsQuery;
use ScoutEngines\Postgres\TsQuery\WebSearchToTsQuery;

class PostgresEngineServiceProvider extends ServiceProvider
{
    public static function builderMacros()
    {
        return [
            'usingPhraseQuery' => PhraseToTsQuery::class,
            'usingPlainQuery' => PlainToTsQuery::class,
            'usingTsQuery' => ToTsQuery::class,
            'usingWebSearchQuery' => WebSearchToTsQuery::class,
        ];
    }

    public function boot()
    {
        $this->app->make(EngineManager::class)->extend('pgsql', function () {
            return new PostgresEngine(
                $this->app->get('db'),
                $this->app->get('config')->get('scout.pgsql', [])
            );
        });

        foreach (self::builderMacros() as $macro => $class) {
            $this->registerBuilderMacro($macro, $class);
        }
    }

    protected function registerBuilderMacro($name, $class)
    {
        if (! Builder::hasMacro($name)) {
            Builder::macro($name, function () use ($class) {
                $this->callback = function ($builder, $config) use ($class) {
                    return new $class($builder->query, $config);
                };

                return $this;
            });
        }
    }
}
