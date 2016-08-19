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
        return $this->decoder->decode($data, $format);
    }
}