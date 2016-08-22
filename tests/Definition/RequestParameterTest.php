<?php
namespace ElevenLabs\Api\Definition;

use PHPUnit\Framework\TestCase;

class RequestParameterTest extends TestCase
{
    /** @test */
    public function itCanBeSerialized()
    {
        $requestParameter = new RequestParameter('query', 'foo', true, new \stdClass());
        $serialized = serialize($requestParameter);

        assertThat(unserialize($serialized), self::equalTo($requestParameter));
    }
}