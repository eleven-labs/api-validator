<?php
namespace ElevenLabs\Swagger;

/**
 * ValueObject that contains constraint violation properties
 */
class ConstraintViolation
{
    /** @var string */
    private $property;

    /** @var string */
    private $message;

    /** @var string */
    private $constraint;

    /** @var string */
    private $location;

    /**
     * @param string $property
     * @param string $message
     * @param string $constraint
     * @param string $location
     */
    public function __construct($property, $message, $constraint, $location)
    {
        $this->property = $property;
        $this->message = $message;
        $this->constraint = $constraint;
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getConstraint()
    {
        return $this->constraint;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    public function toArray()
    {
        return [
            'property' => $this->getProperty(),
            'message' => $this->getMessage(),
            'constraint' => $this->getConstraint(),
            'location' => $this->getLocation()
        ];
    }
}