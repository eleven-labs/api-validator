<?php
namespace ElevenLabs\Api\Validator;

use PHPUnit\Framework\TestCase;

class ConstraintViolationTest extends TestCase
{
    public function testConstraintViolationToArray()
    {
        $expectedArray = [
            'property' => 'property_one',
            'message' => 'a violation message',
            'constraint' => 'required',
            'location' => 'query'
        ];

        $violation = new ConstraintViolation('property_one', 'a violation message', 'required', 'query');

        assertEquals($expectedArray, $violation->toArray());
    }
}
