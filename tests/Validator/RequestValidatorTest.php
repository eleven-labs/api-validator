<?php
namespace ElevenLabs\Api\Validator;

use ElevenLabs\Api\Decoder\Adapter\SymfonyDecoderAdapter;
use ElevenLabs\Api\Factory\SwaggerSchemaFactory;
use GuzzleHttp\Psr7\Request;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonDecode;

/**
 * Class RequestValidatorTest
 */
class RequestValidatorTest extends TestCase
{
    /** @var  RequestValidator */
    private $requestValidator;

    public function setUp()
    {
        $validator = new Validator();
        $decoder = new SymfonyDecoderAdapter(new JsonDecode());
        $schema = (new SwaggerSchemaFactory())->createSchema('file://'.__DIR__.'/../fixtures/petstore.json');

        $this->requestValidator = new RequestValidator(
            $schema,
            $validator,
            $decoder
        );
    }

    /** @test */
    public function itValidateEmptyContentType()
    {
        $expectedViolations = [
            new ConstraintViolation('Content-Type', 'Content-Type should not be empty', 'required', 'header')
        ];

        $request = new Request('POST', 'http://petstore.swagger.io/api/pets');

        $this->requestValidator->validateRequest($request);

        assertThat($this->requestValidator->hasViolations(), isTrue());
        assertThat($this->requestValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->requestValidator->getViolations(), equalTo($expectedViolations));
    }

    /** @test */
    public function itValidateUnsupportedContentType()
    {
        $expectedViolations = [
            new ConstraintViolation(
                'Content-Type',
                'application/unknown is not a supported content type, supported: application/json',
                'enum',
                'header'
            )
        ];

        $request = new Request(
            'POST',
            'http://petstore.swagger.io/api/pets',
            ['Content-Type' => 'application/unknown']
        );

        $this->requestValidator->validateRequest($request);

        assertThat($this->requestValidator->hasViolations(), isTrue());
        assertThat($this->requestValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->requestValidator->getViolations(), equalTo($expectedViolations));
    }

    /** @test */
    public function itValidateHeaders()
    {
        $expectedViolation = [
            new ConstraintViolation(
                'X-Required-Header',
                'The property X-Required-Header is required',
                'required',
                'header'
            )
        ];

        $request = new Request(
            'PATCH',
            'http://petstore.swagger.io/api/pets/1',
            ['Content-Type' => 'application/json'],
            '{"id": 1, "name": "woof"}'
        );

        $this->requestValidator->validateRequest($request);

        assertThat($this->requestValidator->hasViolations(), isTrue());
        assertThat($this->requestValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->requestValidator->getViolations(), equalTo($expectedViolation));
    }

    /** @test */
    public function itValidateTheRequestBody()
    {
        $expectedViolation = [
            new ConstraintViolation('id', 'The property id is required', 'required', 'body'),
            new ConstraintViolation('name', 'The property name is required', 'required', 'body'),
            new ConstraintViolation('', 'Failed to match all schemas', 'allOf', 'body'),
        ];

        $request = new Request(
            'POST',
            'http://petstore.swagger.io/api/pets',
            ['Content-Type' => 'application/json'],
            '{}'
        );

        $this->requestValidator->validateRequest($request);

        assertThat($this->requestValidator->hasViolations(), isTrue());
        assertThat($this->requestValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->requestValidator->getViolations(), equalTo($expectedViolation));
    }

    /** @test */
    public function itValidateQueryParameters()
    {
        $expectedViolation = [
            new ConstraintViolation(
                'limit',
                'String value found, but an integer is required',
                'type',
                'query'
            )
        ];

        $request = new Request(
            'GET',
            'http://petstore.swagger.io/api/pets?tags=foo,bar&limit=no',
            ['Content-Type' => 'application/json']
        );

        $this->requestValidator->validateRequest($request);

        assertThat($this->requestValidator->hasViolations(), isTrue());
        assertThat($this->requestValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->requestValidator->getViolations(), equalTo($expectedViolation));
    }
}