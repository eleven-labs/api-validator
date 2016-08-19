<?php
namespace ElevenLabs\Api\Definition;

class RequestParameters implements \Serializable
{
    /**
     * @var RequestParameter[]
     */
    private $path;

    /**
     * @var RequestParameter[]
     */
    private $query;

    /**
     * @var RequestParameter[]
     */
    private $headers;

    /**
     * @var RequestParameter
     */
    private $body;

    public function __construct(array $parameters)
    {
        foreach ($parameters as $parameter) {
            $this->addParameter($parameter);
        }
    }

    /**
     * JSON Schema for a the body
     *
     * @return null|\stdClass
     */
    public function getBodySchema()
    {
        if ($this->body !== null && $this->body->hasSchema()) {
            return $this->body->getSchema();
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
        if ($this->query !== null) {
            return $this->getSchema($this->query);
        }

        return null;
    }

    /**
     * JSON Schema for the headers
     *
     * @return \stdClass
     */
    public function getHeadersSchema()
    {
        if ($this->headers !== null) {
            return $this->getSchema($this->headers);
        }

        return null;
    }

    /**
     * @return RequestParameter[]
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return RequestParameter[]
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return RequestParameter[]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return RequestParameter
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param RequestParameter[] $parameters
     *
     * @return \stdClass
     */
    private function getSchema(array $parameters)
    {
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
        return serialize([
            'path' => $this->path,
            'query' => $this->query,
            'headers' => $this->headers,
            'body' => $this->body
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->path = $data['path'];
        $this->query = $data['query'];
        $this->headers = $data['headers'];
        $this->body = $data['body'];
    }

    protected function addParameter(RequestParameter $parameter)
    {
        switch($parameter->getLocation()) {
            case 'path':
                $this->path[$parameter->getName()] = $parameter;
                break;
            case 'header':
                $this->headers[$parameter->getName()] = $parameter;
                break;
            case 'query':
                $this->query[$parameter->getName()] = $parameter;
                break;
            case 'body':
                if ($this->body !== null) {
                    throw new \LogicException(
                        sprintf(
                            'Cannot process the "%s" body parameter, You already have specified a "%s" body parameter',
                            $parameter->getName(),
                            $this->body->getName()
                        )
                    );
                }
                $this->body = $parameter;
                break;
            default:
                throw new \InvalidArgumentException($parameter->getLocation(). ' is not a valid parameter location');
                break;
        }
    }
}
