<?php
namespace ElevenLabs\Api\Definition;

use PHPUnit\Framework\TestCase;

class RequestParametersTest extends TestCase
{
    /** @test */
    public function itCanBeTraversed()
    {
        $requestParameter = $this->prophesize(RequestParameter::class);
        $requestParameter->getLocation()->willReturn('query');
        $requestParameter->getName()->willReturn('foo');

        $requestParameters = new RequestParameters([$requestParameter->reveal()]);

        assertThat($requestParameters, isInstanceOf(\Traversable::class));
        assertThat($requestParameters, containsOnlyInstancesOf(RequestParameter::class));
    }

    /** @test */
    public function itCanBeSerialized()
    {
        $requestParameters = new RequestParameters([]);
        $serialized = serialize($requestParameters);

        assertThat(unserialize($serialized), self::equalTo($requestParameters));
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

    /** @test */
    public function itCanResolveARequestParameterByName()
    {
        $requestParameter = $this->prophesize(RequestParameter::class);
        $requestParameter->getLocation()->willReturn('query');
        $requestParameter->getName()->willReturn('foo');

        $requestParameters = new RequestParameters([$requestParameter->reveal()]);

        assertThat($requestParameters->getByName('foo'), equalTo($requestParameter->reveal()));
    }
}