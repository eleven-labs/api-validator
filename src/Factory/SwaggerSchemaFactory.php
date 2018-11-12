<?php
namespace ElevenLabs\Api\Factory;

use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\Parameter;
use ElevenLabs\Api\Definition\Parameters;
use ElevenLabs\Api\Definition\RequestDefinitions;
use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Schema;
use ElevenLabs\Api\JsonSchema\Uri\YamlUriRetriever;
use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use Symfony\Component\Yaml\Yaml;

/**
 * Create a schema definition from a Swagger file
 */
class SwaggerSchemaFactory implements SchemaFactory
{
    /**
     * @param string $schemaFile (must start with a scheme: file://, http:// or https://)
     *
     * @return Schema
     */
    public function createSchema($schemaFile)
    {
        $schema = $this->resolveSchemaFile($schemaFile);

        $host = isset($schema->host) ? $schema->host : null;
        $basePath = isset($schema->basePath) ? $schema->basePath : '';
        $schemes = isset($schema->schemes) ? $schema->schemes : ['http'];

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
                    // @codeCoverageIgnoreStart
                    throw new \InvalidArgumentException(
                        'You need to require the "symfony/yaml" component in order to parse yml files'
                    );
                    // @codeCoverageIgnoreEnd
                }

                $uriRetriever = new YamlUriRetriever();
                break;
            case 'json':
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

        $schemaStorage = new SchemaStorage(
            $uriRetriever,
            new UriResolver()
        );

        $schema = $schemaStorage->getSchema($schemaFile);

        // JsonSchema normally defers resolution of $ref values until validation.
        // That does not work for us, because we need to have the complete schema
        // to build definitions.
        $this->expandSchemaReferences($schema, $schemaStorage);

        return $schema;
    }

    private function expandSchemaReferences(&$schema, SchemaStorage $schemaStorage)
    {
        foreach ($schema as &$member) {
            if (is_object($member) && property_exists($member, '$ref') && is_string($member->{'$ref'})) {
                $member = $schemaStorage->resolveRef($member->{'$ref'});
            }
            if (is_object($member) || is_array($member)) {
                $this->expandSchemaReferences($member, $schemaStorage);
            }
        }
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

        $basePath = isset($schema->basePath) ? $schema->basePath : '';

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

                if (empty($contentTypes) && $this->containsBodyParametersLocations($definition)) {
                    $contentTypes = $this->guessSupportedContentTypes($definition, $pathTemplate);
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

                $responseContentTypes = $defaultProducedContentTypes;
                if (isset($definition->produces)) {
                    $responseContentTypes = $definition->produces;
                }

                $responseDefinitions = [];
                foreach ($definition->responses as $statusCode => $response) {
                    $responseDefinitions[] = $this->createResponseDefinition(
                        $statusCode,
                        $responseContentTypes,
                        $response
                    );
                }

                $definitions[] = new RequestDefinition(
                    $method,
                    $definition->operationId,
                    $basePath.$pathTemplate,
                    new Parameters($requestParameters),
                    $contentTypes,
                    $responseDefinitions
                );
            }
        }

        return new RequestDefinitions($definitions);
    }

    /**
     * @return bool
     */
    private function containsBodyParametersLocations(\stdClass $definition)
    {
        if (!isset($definition->parameters)) {
            return false;
        }

        foreach ($definition->parameters as $parameter) {
            if (isset($parameter->in) && \in_array($parameter->in, Parameter::BODY_LOCATIONS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \stdClass $definition
     * @param string $pathTemplate
     *
     * @return array
     */
    private function guessSupportedContentTypes(\stdClass $definition, $pathTemplate)
    {
        if (!isset($definition->parameters)) {
            return [];
        }

        $bodyLocations = [];
        foreach ($definition->parameters as $parameter) {
            if (isset($parameter->in) && \in_array($parameter->in, Parameter::BODY_LOCATIONS, true)) {
                $bodyLocations[] = $parameter->in;
            }
        }

        if (count($bodyLocations) > 1) {
            throw new \LogicException(
                sprintf(
                    'Parameters cannot have %s locations at the same time in %s',
                    implode(' and ', $bodyLocations),
                    $pathTemplate
                )
            );
        }

        if (count($bodyLocations) === 1) {
            return [Parameter::BODY_LOCATIONS_TYPES[current($bodyLocations)]];
        }

        return [];
    }

    protected function createResponseDefinition($statusCode, array $defaultProducedContentTypes, \stdClass $response)
    {
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
        $schema = isset($parameter['schema']) ? $parameter['schema'] : new \stdClass();
        $required = isset($parameter['required']) ? $parameter['required'] : false;

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
