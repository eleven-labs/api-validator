<?php

namespace Decoder;
use ElevenLabs\Api\Decoder\DecoderUtils;

/**
 * Class DecoderUtilsTest
 *
 * @package Decoder
 */
class DecoderUtilsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataForExtractFormatFromContentType
     * @param $contentType
     */
    public function testExtractFormatFromContentType($contentType, $format)
    {
        self::assertEquals($format, DecoderUtils::extractFormatFromContentType($contentType));
    }

    /**
     * @return array
     */
    public function dataForExtractFormatFromContentType()
    {
        return [
            ['text/plain', 'plain'],
            ['application/xhtml+xml', 'xml']
        ];
    }
}
