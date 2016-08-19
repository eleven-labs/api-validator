<?php
namespace ElevenLabs\Api\Definition;

class ResponseDefinition implements \Serializable
{
    /** @var int */
    private $statusCode;

    /** @var array */
    private $contentTypes;

    /** @var \stdClass */
    private $schema;

    /**
     * ResponseDefinition constructor.
     * @param int $statusCode
     * @param array $contentTypes
     * @param \stdClass $schema
     */
    public function __construct($statusCode, array $contentTypes, \stdClass $schema = null)
    {
        $this->statusCode = $statusCode;
        $this->contentTypes = $contentTypes;
        $this->schema = $schema;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return \stdClass
     */
    public function getSchema()
    {
        return $this->schema;
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
            'schema' => $this->schema
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->statusCode = $data['statusCode'];
        $this->contentTypes = $data['contentTypes'];
        $this->schema = $data['schema'];
    }

}
