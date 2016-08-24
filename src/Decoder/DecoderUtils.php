<?php
namespace ElevenLabs\Api\Decoder;

class DecoderUtils
{
    public static function extractFormatFromContentType($contentType)
    {
        $parts = explode('/', $contentType);
        $format = array_pop($parts);
        if (false !== $pos = strpos($format, '+')) {
            $format = substr($format, $pos+1);
        }

        return $format;
    }
}