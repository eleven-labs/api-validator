<?php
namespace ElevenLabs\Api\Definition;

class RequestDefinitions implements \Serializable, \IteratorAggregate
{
    /**
     * @var array
     */
    private $definitions;

    public function __construct(array $requestDefinitions = [])
    {
        foreach ($requestDefinitions as $requestDefinition) {
            $this->addRequestDefinition($requestDefinition);
        }
    }

    /**
     * @param string $operationId
     *
     * @return RequestDefinition
     */
    public function getRequestDefinition($operationId)
    {
        if (!isset($this->definitions[$operationId])) {
            throw new \InvalidArgumentException('Unable to find request definition for operationId '.$operationId);
        }

        return $this->definitions[$operationId];
    }

    private function addRequestDefinition(RequestDefinition $requestDefinition)
    {
        $this->definitions[$requestDefinition->getOperationId()] = $requestDefinition;
    }

    /**
     * @return \ArrayIterator|RequestDefinition[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->definitions);
    }

    public function serialize()
    {
        return serialize([
            'definitions' => $this->definitions
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->definitions = $data['definitions'];
    }
}
