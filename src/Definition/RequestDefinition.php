<?php declare(strict_types=1);

namespace ElevenLabs\Api\Definition;

use stdClass;

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

    /** @var string[] */
    private $contentTypes;

    /** @var ResponseDefinition[] */
    private $responses;

    /**
     * @param string[] $contentTypes
     * @param ResponseDefinition[] $responses
     */
    public function __construct(
        string $method,
        string $operationId,
        string $pathTemplate,
        Parameters $parameters,
        array $contentTypes,
        array $responses
    ) {
        $this->method = $method;
        $this->operationId = $operationId;
        $this->pathTemplate = $pathTemplate;
        $this->parameters = $parameters;
        $this->contentTypes = $contentTypes;
        foreach ($responses as $response) {
            $this->addResponseDefinition($response);
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getOperationId(): string
    {
        return $this->operationId;
    }

    public function getPathTemplate(): string
    {
        return $this->pathTemplate;
    }

    public function getRequestParameters(): Parameters
    {
        return $this->parameters;
    }

    public function getContentTypes(): array
    {
        return $this->contentTypes;
    }

    public function getResponseDefinition(int $statusCode): ResponseDefinition
    {
        if (isset($this->responses[$statusCode])) {
            return $this->responses[$statusCode];
        }

        if (isset($this->responses['default'])) {
            return $this->responses['default'];
        }

        throw new \InvalidArgumentException(
            sprintf(
                'No response definition for %s %s is available for status code %s',
                $this->method,
                $this->pathTemplate,
                $statusCode
            )
        );
    }

    public function hasBodySchema(): bool
    {
        return $this->parameters->hasBodySchema();
    }

    public function getBodySchema(): ?stdClass
    {
        return $this->parameters->getBodySchema();
    }

    public function hasHeadersSchema(): bool
    {
        return $this->parameters->hasHeadersSchema();
    }

    public function getHeadersSchema(): ?stdClass
    {
        return $this->parameters->getHeadersSchema();
    }

    public function hasPathSchema(): bool
    {
        return $this->parameters->hasPathSchema();
    }

    public function getPathSchema(): ?stdClass
    {
        return $this->parameters->getPathSchema();
    }

    public function hasQueryParametersSchema(): bool
    {
        return $this->parameters->hasQueryParametersSchema();
    }

    public function getQueryParametersSchema(): ?stdClass
    {
        return $this->parameters->getQueryParametersSchema();
    }

    // Serializable
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

    // Serializable
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

    private function addResponseDefinition(ResponseDefinition $response)
    {
        $this->responses[$response->getStatusCode()] = $response;
    }
}
