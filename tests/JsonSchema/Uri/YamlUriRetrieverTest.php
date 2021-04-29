<?php
namespace ElevenLabs\Api\JsonSchema\Uri;

use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\isType;

class YamlUriRetrieverTest extends TestCase
{
    public function testItCanLoadAYamlFile()
    {
        $retriever = new YamlUriRetriever();
        $object = $retriever->retrieve('file://'.__DIR__.'/../../fixtures/petstore.yaml');

        assertThat($object, isType('object'));
    }
}
