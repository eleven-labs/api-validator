<?php
namespace ElevenLabs\Api\Definition;

class RequestParameters implements \Serializable, \IteratorAggregate
{
    /**
     * @var RequestParameter[]
     */
    private $parameters;

    public function __construct(array $parameters)
    {
        foreach ($parameters as $parameter) {
            $this->addParameter($parameter);
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->parameters);
    }

    /**
     * JSON Schema for a the body
     *
     * @return \stdClass|null
     */
    public function getBodySchema()
    {
        $body = $this->getBody();
        if ($body !== null && $body->hasSchema()) {
            return $body->getSchema();
        }

        return null;
    }

    /**
     * JSON Schema for a the query parameters
     *
     * @return \stdClass
     */
    public function getQuerySchema()
    {
        return $this->getSchema($this->getQuery());
    }

    /**
     * JSON Schema for the headers
     *
     * @return \stdClass
     */
    public function getHeadersSchema()
    {
        return $this->getSchema($this->getHeaders());
    }

    /**
     * @return RequestParameter[]
     */
    public function getPath()
    {
        return $this->findByLocation('path');
    }

    /**
     * @return RequestParameter[]
     */
    public function getQuery()
    {
        return $this->findByLocation('query');
    }

    /**
     * @return RequestParameter[]
     */
    public function getHeaders()
    {
        return $this->findByLocation('header');
    }

    /**
     * @return RequestParameter|null
     */
    public function getBody()
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
     * @return RequestParameter|null
     */
    public function getByName($name)
    {
        if (! isset($this->parameters[$name])) {
            return null;
        }

        return $this->parameters[$name];
    }

    /**
     * @param RequestParameter[] $parameters
     *
     * @return \stdClass|null
     */
    private function getSchema(array $parameters)
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

    public function serialize()
    {
        return serialize(['parameters' => $this->parameters]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->parameters = $data['parameters'];
    }

    private function findByLocation($location)
    {
        return array_filter(
            $this->parameters,
            function (RequestParameter $parameter) use ($location) {
                return $parameter->getLocation() === $location;
            }
        );
    }

    private function addParameter(RequestParameter $parameter)
    {
        $validLocations = ['path', 'header', 'query', 'body'];
        if (!in_array($parameter->getLocation(), $validLocations)) {
            throw new \InvalidArgumentException(
                $parameter->getLocation(). ' is not a valid parameter location'
            );
        }

        $this->parameters[$parameter->getName()] = $parameter;
    }
}
