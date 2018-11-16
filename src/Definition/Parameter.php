<?php declare(strict_types=1);

namespace ElevenLabs\Api\Definition;

class Parameter implements \Serializable
{
    const LOCATIONS = ['path', 'header', 'query', 'body', 'formData'];
    const BODY_LOCATIONS = ['formData', 'body'];
    const BODY_LOCATIONS_TYPES = ['formData' => 'application/x-www-form-urlencoded', 'body'  => 'application/json'];

    /** @var string */
    private $location;

    /** @var string */
    private $name;

    /** @var bool */
    private $required;

    /** @var ?object */
    private $schema;

    /**
     * @throws \InvalidArgumentException If the location is not supported.
     */
    public function __construct(
        string $location,
        string $name,
        bool $required = false,
        ?object $schema = null
    ) {
        if (! in_array($location, self::LOCATIONS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s is not a supported parameter location, supported: %s',
                    $location,
                    implode(', ', self::LOCATIONS)
                )
            );
        }

        $this->location = $location;
        $this->name = $name;
        $this->required = $required;
        $this->schema = $schema;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getSchema(): ?object
    {
        return $this->schema;
    }

    public function hasSchema(): bool
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
