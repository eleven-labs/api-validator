<?php
namespace ElevenLabs\Api\Decoder;

interface DecoderInterface
{
    /**
     * Decode a string into PHP data
     *
     * @param string $data
     * @param string $format
     *
     * @return array
     */
    public function decode($data, $format);
}