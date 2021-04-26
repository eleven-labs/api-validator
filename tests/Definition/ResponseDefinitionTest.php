<?php
namespace ElevenLabs\Api\Definition;

use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertThat;

class ResponseDefinitionTest extends TestCase
{
    /** @test */
    public function itCanBeSerialized()
    {
        $responseDefinition = new ResponseDefinition(200, ['application/json'], new Parameters([]));
        $serialized = serialize($responseDefinition);

        assertThat(unserialize($serialized), self::equalTo($responseDefinition));
    }
}
