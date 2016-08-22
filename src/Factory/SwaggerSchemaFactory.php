<?php
namespace ElevenLabs\Api\Factory;

use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\RequestParameter;
use ElevenLabs\Api\Definition\RequestParameters;
use ElevenLabs\Api\Definition\RequestDefinitions;
use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Schema;
use ElevenLabs\Api\JsonSchema\Uri\YamlUriRetriever;
use JsonSchema\RefResolver;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use Symfony\Component\Yaml\Yaml;

/**
 * Create a schema definition from a Swagger file
 */
class SwaggerSchemaFactory implements SchemaFactory
{
    /**
     * @param string $schemaFile (must start with a scheme: file://, http://, https://, etc...)
     * @return Schema
     */
    public function createSchema($schemaFile)
    {
        $schema = $this->resolveSchemaFile($schemaFile);

        $host = (isset($schema->host)) ? $schema->host : null;
        $basePath = (isset($schema->basePath)) ? $schema->basePath : '';
        $schemes = (isset($schema->schemes)) ? $schema->schemes : ['http'];

        return new Schema(
            $this->createRequestDefinitions($schema),
            $basePath,
            $host,
            $schemes
        );
    }

    /**
     *
     * @param string $schemaFile
     *
     * @return object
     */
    private function resolveSchemaFile($schemaFile)
    {
        $extension = pathinfo($schemaFile, PATHINFO_EXTENSION);
        switch ($extension) {
            case 'yml':
            case 'yaml':
                if (!class_exists(Yaml::class)) {
                    throw new \InvalidArgumentException(
                        'You need to require the "symfony/yaml" component in order to parse yml files'
                    );
                }
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

        return $refResolver->resolve($schemaFile);
    }

    /**
     * @param \stdClass $schema
     * @return RequestDefinitions
     */
    private function createRequestDefinitions(\stdClass $schema)
    {
        $definitions = [];
        $defaultConsumedContentTypes = [];
        $defaultProducedContentTypes = [];

        if (isset($schema->consumes)) {
            $defaultConsumedContentTypes = $schema->consumes;
        }
        if (isset($schema->produces)) {
            $defaultProducedContentTypes = $schema->produces;
        }

        foreach ($schema->paths as $pathTemplate => $methods) {
            foreach ($methods as $method => $definition) {
                $method = strtoupper($method);
                $contentTypes = $defaultConsumedContentTypes;
                if (isset($definition->consumes)) {
                    $contentTypes = $definition->consumes;
                }

                if (!isset($definition->operationId)) {
                    throw new \LogicException(
                        sprintf(
                            'You need to provide an operationId for %s %s',
                            $method,
                            $pathTemplate
                        )
                    );
                }

                if (empty($contentTypes)) {
                    throw new \LogicException(
                        sprintf(
                            'You need to specify at least one ContentType for %s %s',
                            $method,
                            $pathTemplate
                        )
                    );
                }

                if (!isset($definition->responses)) {
                    throw new \LogicException(
                        sprintf(
                            'You need to specify at least one response for %s %s',
                            $method,
                            $pathTemplate
                        )
                    );
                }

                if (!isset($definition->parameters)) {
                    $definition->parameters = [];
                }

                $requestParameters = [];
                foreach ($definition->parameters as $parameter) {
                    $requestParameters[] = $this->createRequestParameter($parameter);
                }

                $responseDefinitions = [];
                foreach ($definition->responses as $statusCode => $response) {
                    $responseDefinitions[] = $this->createResponseDefinition(
                        $statusCode,
                        $defaultProducedContentTypes,
                        $response
                    );
                }

                $definitions[] = new RequestDefinition(
                    $method,
                    $definition->operationId,
                    $pathTemplate,
                    new RequestParameters($requestParameters),
                    $contentTypes,
                    $responseDefinitions
                );
            }
        }

        return new RequestDefinitions($definitions);
    }

    private function createResponseDefinition($statusCode, array $defaultProducedContentTypes, \stdClass $response)
    {
        $schema = null;
        $contentTypes = $defaultProducedContentTypes;
        if (isset($response->schema)) {
            $schema = $response->schema;
        }
        if (isset($response->produces)) {
            $contentTypes = $defaultProducedContentTypes;
        }

        return new ResponseDefinition($statusCode, $contentTypes, $schema);
    }

    /**
     * Create a Parameter from a swagger request parameter
     *
     * @param \stdClass $parameter
     *
     * @return RequestParameter
     */
    private function createRequestParameter(\stdClass $parameter)
    {
        $parameter = get_object_vars($parameter);
        $location = $parameter['in'];
        $name = $parameter['name'];
        $schema = (isset($parameter['schema'])) ? $parameter['schema'] : new \stdClass();
        $required = (isset($parameter['required'])) ? $parameter['required'] : false;

        unset($parameter['in']);
        unset($parameter['name']);
        unset($parameter['required']);
        unset($parameter['schema']);

        // Every remaining parameter may be json schema properties
        foreach ($parameter as $key => $value) {
            $schema->{$key} = $value;
        }

        // It's not relevant to validate file type
        if (isset($schema->format) && $schema->format === 'file') {
            $schema = null;
        }

        return new RequestParameter($location, $name, $required, $schema);
    }
}