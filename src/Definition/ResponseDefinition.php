<?php
namespace ElevenLabs\Api\Definition;

class ResponseDefinition implements \Serializable, MessageDefinition
{
    /** @var int */
    private $statusCode;

    /** @var array */
    private $contentTypes;

    /** @var Parameters */
    private $parameters;

    /**
     * @param int $statusCode
     * @param array $allowedContentTypes
     * @param Parameters $parameters
     */
    public function __construct($statusCode, array $allowedContentTypes, Parameters $parameters)
    {
        $this->statusCode = $statusCode;
        $this->contentTypes = $allowedContentTypes;
        $this->parameters = $parameters;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return bool
     */
    public function hasBodySchema()
    {
        return $this->parameters->hasBodySchema();
    }

    /**
     * @return \stdClass
     */
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

    /**
     * Supported response types
     * @return array
     */
    public function getContentTypes()
    {
        return $this->contentTypes;
    }

    public function serialize()
    {
        return serialize([
            'statusCode' => $this->statusCode,
            'contentTypes' => $this->contentTypes,
            'parameters' => $this->parameters
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->statusCode = $data['statusCode'];
        $this->contentTypes = $data['contentTypes'];
        $this->parameters = $data['parameters'];
    }

}
