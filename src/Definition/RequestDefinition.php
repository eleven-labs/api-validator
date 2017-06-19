<?php
namespace ElevenLabs\Api\Definition;

class RequestDefinition implements \Serializable, MessageDefinition
{
    /** @var string */
    private $method;

    /** @var string */
    private $operationId;

    /** @var string */
    private $pathTemplate;

    /** @var Parameters */
    private $parameters;

    /** @var array */
    private $contentTypes;

    /** @var ResponseDefinition[] */
    private $responses;

    /**
     * @param string $method
     * @param string $operationId
     * @param string $pathTemplate
     * @param Parameters $parameters
     * @param array $contentTypes
     * @param ResponseDefinition[] $responses
     */
    public function __construct($method, $operationId, $pathTemplate, Parameters $parameters, array $contentTypes, array $responses)
    {
        $this->method = $method;
        $this->operationId = $operationId;
        $this->pathTemplate = $pathTemplate;
        $this->parameters = $parameters;
        $this->contentTypes = $contentTypes;
        foreach ($responses as $response) {
            $this->addResponseDefinition($response);
        }
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getOperationId()
    {
        return $this->operationId;
    }

    /**
     * @return string
     */
    public function getPathTemplate()
    {
        return $this->pathTemplate;
    }

    /**
     * @return Parameters
     */
    public function getRequestParameters()
    {
        return $this->parameters;
    }

    /**
     * Supported content types
     *
     * @return array
     */
    public function getContentTypes()
    {
        return $this->contentTypes;
    }

    /**
     * @param $statusCode
     * @return ResponseDefinition
     */
    public function getResponseDefinition($statusCode)
    {
        if (isset($this->responses[$statusCode])) {
            return $this->responses[$statusCode];
        } else if (isset($this->responses['default'])) {
            return $this->responses['default'];
        } else {
            throw new \InvalidArgumentException(
                sprintf(
                    'No response definition for %s %s is available for status code %s',
                    $this->method,
                    $this->pathTemplate,
                    $statusCode
                )
            );
        }
    }

    public function hasBodySchema()
    {
        return $this->parameters->hasBodySchema();
    }

    public function getBodySchema()
    {
        return $this->parameters->getBodySchema();
    }

    public function hasHeadersSchema()
    {
        return $this->parameters->hasHeadersSchema();
    }

    public function getHeadersSchema()
    {
        return $this->parameters->getHeadersSchema();
    }

    public function hasQueryParametersSchema()
    {
        return $this->parameters->hasQueryParametersSchema();
    }

    public function getQueryParametersSchema()
    {
        return $this->parameters->getQueryParametersSchema();
    }

    private function addResponseDefinition(ResponseDefinition $response)
    {
        $this->responses[$response->getStatusCode()] = $response;
    }

    public function serialize()
    {
        return serialize([
            'method' => $this->method,
            'operationId' => $this->operationId,
            'pathTemplate' => $this->pathTemplate,
            'parameters' => $this->parameters,
            'contentTypes' => $this->contentTypes,
            'responses' => $this->responses
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->method = $data['method'];
        $this->operationId = $data['operationId'];
        $this->pathTemplate = $data['pathTemplate'];
        $this->parameters = $data['parameters'];
        $this->contentTypes = $data['contentTypes'];
        $this->responses = $data['responses'];
    }
}
