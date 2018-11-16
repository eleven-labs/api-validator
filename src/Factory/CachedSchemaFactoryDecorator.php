<?php
namespace ElevenLabs\Api\Factory;

use ElevenLabs\Api\Schema;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Load/Store API Schema from Cache
 */
class CachedSchemaFactoryDecorator implements SchemaFactory
{
    /** @var SchemaFactory */
    private $schemaFactory;

    /** @var CacheItemPoolInterface */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache, SchemaFactory $schemaFactory)
    {
        $this->cache = $cache;
        $this->schemaFactory = $schemaFactory;
    }

    public function createSchema(string $schemaFile): Schema
    {
        $cacheKey = hash('sha1', $schemaFile);
        $item = $this->cache->getItem($cacheKey);
        if ($item->isHit()) {
            $schema = $item->get();
        } else {
            $schema = $this->schemaFactory->createSchema($schemaFile);
            $this->cache->save($item->set($schema));
        }

        return $schema;
    }
}
