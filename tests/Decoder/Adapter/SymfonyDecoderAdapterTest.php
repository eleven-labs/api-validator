<?php
namespace ElevenLabs\Api\Decoder\Adapter;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\equalTo;

class SymfonyDecoderAdapterTest extends TestCase
{
    /** @test */
    public function itShouldTransformDecodedXmlIntoAnArrayOfObject()
    {
        $expected = [
            (object) ['@key' => 0, 'foo' => 'foo1'],
            (object) ['@key' => 1, 'foo' => 'foo2'],
        ];

        $data = '<response><item key="0"><foo>foo1</foo></item><item key="1"><foo>foo2</foo></item></response>';
        $decoder = new SymfonyDecoderAdapter(new XmlEncoder());
        $actual = $decoder->decode($data, 'xml');

        assertThat($actual, equalTo($expected));
    }

    /** @test */
    public function itShouldDecodeAJsonStringIntoAnArrayOfObject()
    {
        $expected = [
            (object) ['foo' => 'foo1'],
            (object) ['foo' => 'foo2'],
        ];

        $data = '[{"foo": "foo1"}, {"foo": "foo2"}]';

        $decoder = new SymfonyDecoderAdapter(new JsonDecode(true));
        $actual = $decoder->decode($data, 'json');

        assertThat($actual, equalTo($expected));
    }
}
