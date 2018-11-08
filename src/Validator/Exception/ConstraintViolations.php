<?php
namespace ElevenLabs\Api\Validator\Exception;

use ElevenLabs\Api\Validator\ConstraintViolation;

class ConstraintViolations extends \Exception
{
    private $violations;

    /**
     * @param ConstraintViolation[] $violations
     */
    public function __construct(array $violations)
    {
        $this->violations = $violations;
        parent::__construct((string) $this);
    }

    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $message = "Request constraint violations:\n";
        foreach ($this->violations as $violation) {
            $message .= sprintf(
                "[property]: %s\n[message]: %s\n[constraint]: %s\n[location]: %s\n\n",
                $violation->getProperty(),
                $violation->getMessage(),
                $violation->getConstraint(),
                $violation->getLocation()
            );
        }

        return $message;
    }
}
