<?php
namespace ElevenLabs\Api\Validator;

use JsonSchema\RefResolver;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use ElevenLabs\Api\Validator\JsonSchema\Uri\YamlUriRetriever;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Load a Schema from an OpenAPI/Swagger 2.0 definition
 */
class SchemaLoader
{
    /**
     * @var ConfigCacheInterface|null
     */
    private $cache;

    public function __construct(ConfigCacheInterface $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $schemaFile A Swagger file
     *
     * @return Schema
     */
    public function load($schemaFile)
    {
        if ($this->cache !== null) {
            $schemaObject = $this->loadSchemaFromCache($schemaFile);
        } else {
            $schemaObject = $this->loadSchemaObject($schemaFile);
        }

        return new Schema($schemaObject);
    }

    /**
     * @param string $schemaFile Path to the schema
     *
     * @return object
     */
    private function loadSchemaFromCache($schemaFile)
    {
        if ($this->cache->isFresh() === false) {
            $schemaObject = $this->loadSchemaObject($schemaFile);
            $this->cache->write(
                "<?php\n return unserialize('".serialize($schemaObject)."');",
                [new FileResource($schemaFile)]
            );

        } else {
            $schemaObject = require $this->cache->getPath();
        }

        return $schemaObject;
    }

    /**
     * @param $schemaFile
     *
     * @return object
     */
    private function loadSchemaObject($schemaFile)
    {
        $extension = pathinfo($schemaFile, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'yml':
            case 'yaml':
                $uriRetriever = new YamlUriRetriever();
                break;
            case 'json';
                $uriRetriever = new UriRetriever();
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'file "%s" does not provide a supported extension choose either json, yml or yaml',
                        $schemaFile
                    )
                );
        }

        $refResolver = new RefResolver(
            $uriRetriever,
            new UriResolver()
        );

        return $refResolver->resolve('file://'.$schemaFile);
    }
}