<?php
namespace ElevenLabs\Api\Decoder\Adapter;

use ElevenLabs\Api\Decoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\DecoderInterface as SymfonyDecoderInterface;

class SymfonyDecoderAdapter implements DecoderInterface
{
    /**
     * @var SymfonyDecoderInterface
     */
    private $decoder;

    public function __construct(SymfonyDecoderInterface $decoder)
    {
        $this->decoder = $decoder;
    }

    public function decode($data, $format)
    {
        $context = [];

        if ($format === 'json') {
            // the JSON schema validator need an object hierarchy
            $context['json_decode_associative'] = false;
        }

        $decoded = $this->decoder->decode($data, $format, $context);

        if ($format === 'xml') {
            // the JSON schema validator need an object hierarchy
            $decoded = json_decode(json_encode($decoded));
        }

        return $decoded;
    }
}
