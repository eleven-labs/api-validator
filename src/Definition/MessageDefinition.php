<?php declare(strict_types=1);

namespace ElevenLabs\Api\Definition;

interface MessageDefinition
{
    /**
     * Get a list of supported content types.
     *
     * @return string[]
     */
    public function getContentTypes(): array;

    /**
     * Check if a schema for body is available.
     */
    public function hasBodySchema(): bool;

    /**
     * Get the schema for the body.
     */
    public function getBodySchema(): ?object;

    /**
     * Check if a schema for headers is available.
     */
    public function hasHeadersSchema(): bool;

    /**
     * Get the schema for the headers.
     */
    public function getHeadersSchema(): ?object;
}
