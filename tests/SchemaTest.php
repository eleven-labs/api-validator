<?php
namespace ElevenLabs\Api;

use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\RequestDefinitions;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\assertTrue;
use function PHPUnit\Framework\equalTo;

class SchemaTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function itCanIterateAvailableOperations()
    {
        $request = $this->prophesize(RequestDefinition::class);
        $request->getMethod()->willReturn('GET');
        $request->getPathTemplate()->willReturn('/api/pets/{id}');
        $request->getOperationId()->willReturn('getPet');

        $requests = new RequestDefinitions([$request->reveal()]);

        $schema = new Schema($requests);

        $operations = $schema->getRequestDefinitions();

        assertTrue(is_iterable($operations));

        foreach ($operations as $operationId => $operation) {
            assertThat($operationId, equalTo('getPet'));
        }
    }

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

        $requests = new RequestDefinitions();

        $schema = new Schema($requests, '/api');
        $schema->findOperationId('GET', '/api/pets/1234');
    }

    /** @test */
    public function itProvideARequestDefinition()
    {
        $request = $this->prophesize(RequestDefinition::class);
        $request->getMethod()->willReturn('GET');
        $request->getPathTemplate()->willReturn('/pets/{id}');
        $request->getOperationId()->willReturn('getPet');

        $requests = new RequestDefinitions([$request->reveal()]);

        $schema = new Schema($requests, '/api');
        $actual = $schema->getRequestDefinition('getPet');

        assertThat($actual, equalTo($request->reveal()));
    }

    /** @test */
    public function itThrowAnExceptionWhenNoRequestDefinitionIsFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to find request definition for operationId getPet');

        $requests = new RequestDefinitions();

        $schema = new Schema($requests, '/api');
        $schema->getRequestDefinition('getPet');
    }

    /** @test */
    public function itCanBeSerialized()
    {
        $requests = new RequestDefinitions();

        $schema = new Schema($requests);
        $serialized = serialize($schema);

        assertThat(unserialize($serialized), equalTo($schema));
    }
}
