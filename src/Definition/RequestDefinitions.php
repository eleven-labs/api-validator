<?php declare(strict_types=1);

namespace ElevenLabs\Api\Definition;

class RequestDefinitions implements \Serializable, \IteratorAggregate
{
    /** @var RequestDefinition[] */
    private $definitions = [];

    public function __construct(array $requestDefinitions = [])
    {
        foreach ($requestDefinitions as $requestDefinition) {
            $this->addRequestDefinition($requestDefinition);
        }
    }

    /**
     * @throws \InvalidArgumentException If no request defintion for the operation exists.
     */
    public function getRequestDefinition(string $operationId): RequestDefinition
    {
        if (isset($this->definitions[$operationId])) {
            return $this->definitions[$operationId];
        }

        throw new \InvalidArgumentException('Unable to find request definition for operationId ' . $operationId);
    }

    // IteratorAggregate
    public function getIterator(): iterable
    {
        foreach ($this->definitions as $operationId => $requestDefinition) {
            yield $operationId => $requestDefinition;
        }
    }

    // Serializable
    public function serialize()
    {
        return serialize([
            'definitions' => $this->definitions
        ]);
    }

    // Serializable
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->definitions = $data['definitions'];
    }

    private function addRequestDefinition(RequestDefinition $requestDefinition): void
    {
        $this->definitions[$requestDefinition->getOperationId()] = $requestDefinition;
    }
}
