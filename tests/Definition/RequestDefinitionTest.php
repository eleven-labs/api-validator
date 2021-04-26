<?php
namespace ElevenLabs\Api\Definition;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use function PHPUnit\Framework\assertThat;

class RequestDefinitionTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function itCanBeSerialized()
    {
        $requestDefinition = new RequestDefinition(
            'GET',
            'getFoo',
            '/foo/{id}',
            new Parameters([]),
            ['application/json'],
            []
        );

        $serialized = serialize($requestDefinition);

        assertThat(unserialize($serialized), self::equalTo($requestDefinition));
    }
    /** @test */
    public function itProvideAResponseDefinition()
    {
        $responseDefinition = $this->prophesize(ResponseDefinition::class);
        $responseDefinition->getStatusCode()->willReturn(200);

        $requestDefinition = new RequestDefinition(
            'GET',
            'getFoo',
            '/foo/{id}',
            new Parameters([]),
            ['application/json'],
            [$responseDefinition->reveal()]
        );

        assertThat($requestDefinition->getResponseDefinition(200), self::isInstanceOf(ResponseDefinition::class));
    }

    /** @test */
    public function itProvideAResponseDefinitionUsingDefaultValue()
    {
        $statusCodes = [200, 'default'];
        $responseDefinitions = [];
        foreach ($statusCodes as $statusCode) {
            $responseDefinition = $this->prophesize(ResponseDefinition::class);
            $responseDefinition->getStatusCode()->willReturn($statusCode);
            $responseDefinitions[] = $responseDefinition->reveal();
        }

        $requestDefinition = new RequestDefinition(
            'GET',
            'getFoo',
            '/foo/{id}',
            new Parameters([]),
            ['application/json'],
            $responseDefinitions
        );

        assertThat($requestDefinition->getResponseDefinition(500), self::isInstanceOf(ResponseDefinition::class));
    }

    /** @test */
    public function itThrowAnExceptionWhenNoResponseDefinitionIsFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No response definition for GET /foo/{id} is available for status code 200');

        $requestDefinition = new RequestDefinition(
            'GET',
            'getFoo',
            '/foo/{id}',
            new Parameters([]),
            ['application/json'],
            []
        );

        $requestDefinition->getResponseDefinition(200);
    }
}
