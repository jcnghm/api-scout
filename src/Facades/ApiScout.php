<?php

namespace jcnghm\ApiScout\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \jcnghm\ApiScout\ApiScoutResult analyze(string $endpointKey)
 * @method static array analyzeAll()
 * @method static bool generateComponents(string $endpointKey, array $options = [])
 * @method static \jcnghm\ApiScout\ApiScout addEndpoint(string $key, array $config)
 * @method static array getEndpoints()
 *
 * @see \jcnghm\ApiScout\ApiScout
 */
class ApiScout extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'api-scout';
    }
}