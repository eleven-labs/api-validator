<?php
namespace ElevenLabs\Api\Definition;

use PHPUnit\Framework\TestCase;

class ResponseDefinitionTest extends TestCase
{
    /** @test */
    public function itCanBeSerialized()
    {
        $responseDefinition = new ResponseDefinition(200, ['application/json'], new \stdClass());
        $serialized = serialize($responseDefinition);

        assertThat(unserialize($serialized), self::equalTo($responseDefinition));
    }
}