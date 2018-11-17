<?php declare(strict_types=1);

namespace ElevenLabs\Api\Decoder;

interface DecoderInterface
{
    /**
     * Decode a string into an object or array of objects.
     *
     * @return object|object[]
     */
    public function decode(string $data, string $format);
}
