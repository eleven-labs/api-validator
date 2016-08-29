<?php
namespace ElevenLabs\Api\Decoder;

interface DecoderInterface
{
    /**
     * Decode a string into \stdClass or an array of \stdClass
     *
     * @param string $data
     * @param string $format
     *
     * @return \stdClass
     */
    public function decode($data, $format);
}