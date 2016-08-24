<?php
namespace ElevenLabs\Api\Factory;

use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\Parameter;
use ElevenLabs\Api\Definition\Parameters;
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
    protected function resolveSchemaFile($schemaFile)
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
    protected function createRequestDefinitions(\stdClass $schema)
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
                    $requestParameters[] = $this->createParameter($parameter);
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
                    new Parameters($requestParameters),
                    $contentTypes,
                    $responseDefinitions
                );
            }
        }

        return new RequestDefinitions($definitions);
    }

    protected function createResponseDefinition($statusCode, array $defaultProducedContentTypes, \stdClass $response)
    {
        $schema = null;
        $allowedContentTypes = $defaultProducedContentTypes;
        $parameters = [];
        if (isset($response->schema)) {
            $parameters[] = $this->createParameter((object) [
                'in' => 'body',
                'name' => 'body',
                'required' => true,
                'schema' => $response->schema
            ]);
        }
        if (isset($response->headers)) {
            foreach ($response->headers as $headerName => $schema) {
                $schema->in = 'header';
                $schema->name = $headerName;
                $schema->required = true;
                $parameters[] = $this->createParameter($schema);
            }
        }
        if (isset($response->produces)) {
            $allowedContentTypes = $defaultProducedContentTypes;
        }

        return new ResponseDefinition($statusCode, $allowedContentTypes, new Parameters($parameters));
    }

    /**
     * Create a Parameter from a swagger parameter
     *
     * @param \stdClass $parameter
     *
     * @return Parameter
     */
    protected function createParameter(\stdClass $parameter)
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

        return new Parameter($location, $name, $required, $schema);
    }
}