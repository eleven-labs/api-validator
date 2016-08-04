<?php
namespace ElevenLabs\Api\Validator\JsonSchema\Uri;

use PHPUnit\Framework\TestCase;

class YamlUriRetrieverTest extends TestCase
{
    public function testItCanLoadAYamlFile()
    {
        $retriever = new YamlUriRetriever();
        $object = $retriever->retrieve('file://'.__DIR__.'/../../fixtures/petstore.yml');

        self::assertInternalType('object', $object);
    }
}