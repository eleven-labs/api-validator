<?php
namespace ElevenLabs\Api\Definition;

use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertThat;

class RequestParameterTest extends TestCase
{
    /** @test */
    public function itThrowAnExceptionOnUnsupportedParameterLocation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'nowhere is not a supported parameter location, ' .
            'supported: path, header, query, body, formData'
        );

        $param = new Parameter('nowhere', 'foo');
    }

    /** @test */
    public function itCanBeSerialized()
    {
        $requestParameter = new Parameter('query', 'foo', true, new \stdClass());
        $serialized = serialize($requestParameter);

        assertThat(unserialize($serialized), self::equalTo($requestParameter));
        self::assertTrue($requestParameter->hasSchema());
    }
}
