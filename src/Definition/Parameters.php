<?php declare(strict_types=1);

namespace ElevenLabs\Api\Definition;

class Parameters implements \Serializable, \IteratorAggregate
{
    /**
     * @var Parameter[]
     */
    private $parameters = [];

    public function __construct(array $parameters)
    {
        foreach ($parameters as $parameter) {
            $this->addParameter($parameter);
        }
    }

    // IteratorAggregate
    public function getIterator(): iterable
    {
        foreach ($this->parameters as $name => $parameter) {
            yield $name => $parameter;
        }
    }

    public function hasBodySchema(): bool
    {
        $body = $this->getBody();

        return ($body !== null && $body->hasSchema());
    }

    /**
     * JSON Schema for the body.
     */
    public function getBodySchema(): ?object
    {
        return $this->getBody()->getSchema();
    }

    public function hasPathSchema(): bool
    {
        return $this->getPathSchema() !== null;
    }

    /**
     * JSON Schema for the path parameters.
     */
    public function getPathSchema(): ?object
    {
        return $this->getSchema($this->getPath());
    }

    public function hasQueryParametersSchema(): bool
    {
        return $this->getQueryParametersSchema() !== null;
    }

    /**
     * JSON Schema for the query parameters.
     */
    public function getQueryParametersSchema(): ?object
    {
        return $this->getSchema($this->getQuery());
    }

    public function hasHeadersSchema(): bool
    {
        return $this->getHeadersSchema() !== null;
    }

    /**
     * JSON Schema for the headers.
     */
    public function getHeadersSchema(): ?object
    {
        return $this->getSchema($this->getHeaders());
    }

    /**
     * @return Parameter[]
     */
    public function getPath(): array
    {
        return $this->findByLocation('path');
    }

    /**
     * @return Parameter[]
     */
    public function getQuery(): array
    {
        return $this->findByLocation('query');
    }

    /**
     * @return Parameter[]
     */
    public function getHeaders(): array
    {
        return $this->findByLocation('header');
    }

    public function getBody(): ?Parameter
    {
        $match = $this->findByLocation('body');
        if (empty($match)) {
            return null;
        }

        return current($match);
    }

    /**
     * Get one request parameter by name
     *
     * @param string $name
     * @return Parameter|null
     */
    public function getByName($name)
    {
        if (! isset($this->parameters[$name])) {
            return null;
        }

        return $this->parameters[$name];
    }

    /**
     * @param Parameter[] $parameters
     */
    private function getSchema(array $parameters): ?object
    {
        if (empty($parameters)) {
            return null;
        }

        $schema = new \stdClass();
        $schema->type = 'object';
        $schema->required = [];
        $schema->properties = new \stdClass();
        foreach ($parameters as $name => $parameter) {
            if ($parameter->isRequired()) {
                $schema->required[] = $parameter->getName();
            }
            $schema->properties->{$name} = $parameter->getSchema();
        }

        return $schema;
    }

    // Serializable
    public function serialize()
    {
        return serialize(['parameters' => $this->parameters]);
    }

    // Serializable
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->parameters = $data['parameters'];
    }

    /**
     * @return Parameter[]
     */
    private function findByLocation($location): array
    {
        return array_filter(
            $this->parameters,
            function (Parameter $parameter) use ($location) {
                return $parameter->getLocation() === $location;
            }
        );
    }

    private function addParameter(Parameter $parameter)
    {
        $this->parameters[$parameter->getName()] = $parameter;
    }
}
