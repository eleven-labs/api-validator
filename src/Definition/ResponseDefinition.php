<?php declare(strict_types=1);

namespace ElevenLabs\Api\Definition;

class ResponseDefinition implements \Serializable, MessageDefinition
{
    /** @var int|string */
    private $statusCode;

    /** @var string[] */
    private $contentTypes;

    /** @var Parameters */
    private $parameters;

    /**
     * @param int|string $statusCode
     * @param string[] $allowedContentTypes
     */
    public function __construct($statusCode, array $allowedContentTypes, Parameters $parameters)
    {
        $this->statusCode = $statusCode;
        $this->contentTypes = $allowedContentTypes;
        $this->parameters = $parameters;
    }

    /**
     * @return int|string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function hasBodySchema(): bool
    {
        return $this->parameters->hasBodySchema();
    }

    public function getBodySchema(): ?object
    {
        return $this->parameters->getBodySchema();
    }

    public function hasHeadersSchema(): bool
    {
        return $this->parameters->hasHeadersSchema();
    }

    public function getHeadersSchema(): ?object
    {
        return $this->parameters->getHeadersSchema();
    }

    /**
     * Supported response types.
     *
     * @return string[]
     */
    public function getContentTypes(): array
    {
        return $this->contentTypes;
    }

    // Serializable
    public function serialize()
    {
        return serialize([
            'statusCode' => $this->statusCode,
            'contentTypes' => $this->contentTypes,
            'parameters' => $this->parameters
        ]);
    }

    // Serializable
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->statusCode = $data['statusCode'];
        $this->contentTypes = $data['contentTypes'];
        $this->parameters = $data['parameters'];
    }
}
