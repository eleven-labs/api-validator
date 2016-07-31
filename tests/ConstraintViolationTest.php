<?php
namespace ElevenLabs\Swagger;

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

        self::assertEquals($expectedArray, $violation->toArray());
    }
}