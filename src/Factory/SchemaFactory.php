<?php
namespace ElevenLabs\Api\Factory;

use ElevenLabs\Api\Schema;

interface SchemaFactory
{
    /**
     * Create a Schema definition from an API definition
     *
     * @param string $schemaFile Path to your API definition
     *
     * @return Schema
     */
    public function createSchema($schemaFile);
}