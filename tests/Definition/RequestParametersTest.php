<?php
namespace ElevenLabs\Api\Definition;

use PHPUnit\Framework\TestCase;

class RequestParametersTest extends TestCase
{
    /** @test */
    public function itCanBeSerialized()
    {
        $requestParameters = new RequestParameters([]);
        $serialized = serialize($requestParameters);

        assertThat(unserialize($serialized), self::equalTo($requestParameters));
    }

    /** @test */
    public function itDoesNotAllowMultipleBodyParameters()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot process the "bar" body parameter, You already have specified a "foo" body parameter');

        $firstBody = $this->prophesize(RequestParameter::class);
        $firstBody->getName()->willreturn('foo');
        $firstBody->getLocation()->willreturn('body');

        $secondBody = $this->prophesize(RequestParameter::class);
        $secondBody->getName()->willreturn('bar');
        $secondBody->getLocation()->willreturn('body');

        new RequestParameters([$firstBody->reveal(), $secondBody->reveal()]);
    }

    /** @test */
    public function itThrowAnExceptionOnUnsupportedParameterLocation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('nowhere is not a valid parameter location');

        $param = $this->prophesize(RequestParameter::class);
        $param->getName()->willreturn('foo');
        $param->getLocation()->willreturn('nowhere');

        new RequestParameters([$param->reveal()]);
    }
}