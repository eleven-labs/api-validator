<?php

namespace Decoder;

use ElevenLabs\Api\Decoder\DecoderUtils;
use PHPUnit\Framework\TestCase;

/**
 * Class DecoderUtilsTest
 *
 * @package Decoder
 */
class DecoderUtilsTest extends TestCase
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
