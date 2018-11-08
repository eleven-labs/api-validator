<?php
namespace ElevenLabs\Api;

use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\RequestDefinitions;
use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    /** @test */
    public function itCanResolveAnOperationIdFromAPathAndMethod()
    {
        $request = $this->prophesize(RequestDefinition::class);
        $request->getMethod()->willReturn('GET');
        $request->getPathTemplate()->willReturn('/api/pets/{id}');
        $request->getOperationId()->willReturn('getPet');

        $requests = $this->prophesize(RequestDefinitions::class);
        $requests->getIterator()->willReturn(new \ArrayIterator([$request->reveal()]));

        $schema = new Schema($requests->reveal());

        $operationId = $schema->findOperationId('GET', '/api/pets/1234');

        assertThat($operationId, equalTo('getPet'));
    }

    /** @test */
    public function itThrowAnExceptionWhenNoOperationIdCanBeResolved()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to resolve the operationId for path /api/pets/1234');

        $requests = $this->prophesize(RequestDefinitions::class);
        $requests->getIterator()->willReturn(new \ArrayIterator());

        $schema = new Schema($requests->reveal(), '/api');
        $schema->findOperationId('GET', '/api/pets/1234');
    }

    /** @test */
    public function itProvideARequestDefinition()
    {
        $request = $this->prophesize(RequestDefinition::class);
        $request->getMethod()->willReturn('GET');
        $request->getPathTemplate()->willReturn('/pets/{id}');
        $request->getOperationId()->willReturn('getPet');

        $requests = $this->prophesize(RequestDefinitions::class);
        $requests->getIterator()->willReturn(new \ArrayIterator([$request->reveal()]));

        $schema = new Schema($requests->reveal(), '/api');
        $actual = $schema->getRequestDefinition('getPet');

        assertThat($actual, equalTo($request->reveal()));
    }

    /** @test */
    public function itThrowAnExceptionWhenNoRequestDefinitionIsFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to get the request definition for getPet');

        $requests = $this->prophesize(RequestDefinitions::class);
        $requests->getIterator()->willReturn(new \ArrayIterator());

        $schema = new Schema($requests->reveal(), '/api');
        $schema->getRequestDefinition('getPet');
    }

    /** @test */
    public function itCanBeSerialized()
    {
        $requests = $this->prophesize(RequestDefinitions::class);
        $requests->getIterator()->willReturn(new \ArrayIterator());

        $schema = new Schema($requests->reveal());
        $serialized = serialize($schema);

        assertThat(unserialize($serialized), equalTo($schema));
    }
}
