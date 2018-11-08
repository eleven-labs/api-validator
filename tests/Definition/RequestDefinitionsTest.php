<?php
namespace ElevenLabs\Api\Definition;

use PHPUnit\Framework\TestCase;

class RequestDefinitionsTest extends TestCase
{
    /** @test */
    public function itCanBeSerialized()
    {
        $requestDefinition = new RequestDefinitions([]);
        $serialized = serialize($requestDefinition);

        assertThat(unserialize($serialized), self::equalTo($requestDefinition));
    }

    /** @test */
    public function itProvideARequestDefinition()
    {
        $requestDefinition = $this->prophesize(RequestDefinition::class);
        $requestDefinition->getOperationId()->willReturn('getFoo');

        $requestDefinitions = new RequestDefinitions([$requestDefinition->reveal()]);

        assertThat($requestDefinitions->getRequestDefinition('getFoo'), self::isInstanceOf(RequestDefinition::class));
    }

    /** @test */
    public function itThrowAnExceptionWhenNoRequestDefinitionIsFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find request definition for operationId getFoo');

        $requestDefinitions = new RequestDefinitions([]);
        $requestDefinitions->getRequestDefinition('getFoo');
    }
}
