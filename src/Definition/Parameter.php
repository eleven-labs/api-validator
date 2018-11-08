<?php
namespace ElevenLabs\Api\Definition;

class Parameter implements \Serializable
{
    /**
     * Location of the parameter in the request
     *
     * @var string
     */
    private $location;

    /**
     * @var string
     */
    private $name;

    /**
     * Indicate if the parameter should be present
     *
     * @var bool
     */
    private $required;

    /**
     * A JSON Schema object
     *
     * @var \stdClass
     */
    private $schema;

    public function __construct($location, $name, $required, \stdClass $schema = null)
    {
        $this->location = $location;
        $this->name = $name;
        $this->required = $required;
        $this->schema = $schema;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return \stdClass
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * @return bool
     */
    public function hasSchema()
    {
        return $this->schema !== null;
    }

    public function serialize()
    {
        return serialize([
            'location' => $this->location,
            'name' => $this->name,
            'required' => $this->required,
            'schema' => $this->schema
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $this->location = $data['location'];
        $this->name = $data['name'];
        $this->required  = $data['required'];
        $this->schema  = $data['schema'];
    }
}
