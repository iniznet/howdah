<?php

/**
 * The series module. It registers the feature's repository under its class
 * and, at boot, resolves the content package's Registrar contract for the
 * declarations config/content-types.php carries — the same path every other
 * feature's content model takes, with no registration of its own to keep.
 */

declare(strict_types=1);

namespace Iniznet\Howdah\Features\Series;

use Iniznet\Mahout\Content\PostReader;
use Iniznet\Mahout\Fields\Contracts\FieldReader;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\Module;

final class SeriesModule implements Module
{
    public function register(Container $container): void
    {
        $container->set(new SeriesRepository(
            reader: $container->get(PostReader::class),
            fields: $container->get(FieldReader::class),
            mapper: new SeriesMapper(),
        ));
    }

    public function boot(Container $container): void
    {
        // The content model is declared in config/content-types.php and
        // registered by the theme's ContentProvider; a module boots after
        // every provider, so nothing is attached here.
    }
}
