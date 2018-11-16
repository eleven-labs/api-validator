<?php declare(strict_types=1);

namespace ElevenLabs\Api\Definition;

use stdClass;

interface MessageDefinition
{
    /**
     * Get a list of supported content types.
     *
     * @return string[]
     */
    public function getContentTypes(): array;

    public function hasBodySchema(): bool;

    public function getBodySchema(): ?stdClass;

    public function hasHeadersSchema(): bool;

    public function getHeadersSchema(): ?stdClass;
}
