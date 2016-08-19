<?php
namespace ElevenLabs\Api\JsonSchema\Uri;

use PHPUnit\Framework\TestCase;

class YamlUriRetrieverTest extends TestCase
{
    public function testItCanLoadAYamlFile()
    {
        $retriever = new YamlUriRetriever();
        $object = $retriever->retrieve('file://'.__DIR__.'/../../fixtures/petstore.yml');

        assertThat($object, isType('object'));
    }
}