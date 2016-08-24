<?php
namespace ElevenLabs\Api\Definition;

interface MessageDefinition
{
    /**
     * An array of supported content types
     *
     * @return array
     */
    public function getContentTypes();

    /**
     * @return bool
     */
    public function hasBodySchema();

    /**
     * @return \stdClass
     */
    public function getBodySchema();

    /**
     * @return bool
     */
    public function hasHeadersSchema();

    /**
     * @return \stdClass
     */
    public function getHeadersSchema();

}