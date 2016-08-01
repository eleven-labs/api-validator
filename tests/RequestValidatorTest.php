<?php
namespace ElevenLabs\Swagger;

use GuzzleHttp\Psr7\Request;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;

class RequestValidatorTest extends TestCase
{
    public function testValidateMediaType()
    {
        $validator = $this->prophesize(Validator::class);

        $schema = $this->prophesize(Schema::class);
        $schema->getRequestMediaTypes('/', 'GET')->willReturn(['application/xml']);

        $requestValidator = new RequestValidator($schema->reveal(), $validator->reveal());
        $requestValidator->validateMediaType('application/json', '/', 'GET');

        self::assertTrue($requestValidator->hasViolations());
        self::assertCount(1, $requestValidator->getViolations());
    }

    public function testValidateRequestBody()
    {
        $bodyString = '{"foo": "bar"}';
        $jsonSchema = new \stdClass;
        $jsonSchemaErrors = [
            [
                'property' => 'property',
                'message' => 'message',
                'constraint' => 'constraint'
            ]
        ];

        $validator = $this->prophesize(Validator::class);
        $validator->reset()->willReturn(null);
        $validator->check(json_decode($bodyString), $jsonSchema)->willReturn(null);
        $validator->getErrors()->willReturn($jsonSchemaErrors);

        $schema = $this->prophesize(Schema::class);
        $schema->getRequestSchema('/', 'GET')->willReturn($jsonSchema);

        $requestValidator = new RequestValidator($schema->reveal(), $validator->reveal());
        $requestValidator->validateBodyString($bodyString, '/', 'GET');

        $expectedViolation = new ConstraintViolation('property', 'message', 'constraint', 'body');

        self::assertEquals($expectedViolation, $requestValidator->getViolations()[0]);
    }

    public function testValidateRequestHeaders()
    {
        $headersInSchema = [
            (object) [
                'in' => 'header',
                'name' => 'x-foo',
                'type' => 'integer',
                'required' => true
            ]
        ];

        $expectedHeaders = (object) ['x-foo' => 'bar'];

        $expectedJsonSchema = (object) [
            'type' => 'object',
            'required' => ['x-foo'],
            'properties' => (object) [
                'x-foo' => (object) [
                    'type' => 'integer'
                ]
            ]
        ];

        $validator = $this->prophesize(Validator::class);
        $validator->reset()->willReturn(null);
        $validator->check($expectedHeaders, $expectedJsonSchema)->willReturn(null);
        $validator->getErrors()->willReturn([]);

        $schema = $this->prophesize(Schema::class);
        $schema->getRequestHeadersParameters('/', 'GET')->willReturn($headersInSchema);

        $requestValidator = new RequestValidator($schema->reveal(), $validator->reveal());
        $requestValidator->validateHeaders(['x-foo' => ['bar']], '/', 'GET');
    }

    public function testValidateQueryParams()
    {
        $queryParamsInSchema = [
            (object) [
                'in' => 'query',
                'name' => 'foo',
                'type' => 'integer',
                'required' => true
            ]
        ];

        $expectedQueryParams = (object) ['foo' => 1234];

        $expectedJsonSchema = (object) [
            'type' => 'object',
            'required' => ['foo'],
            'properties' => (object) [
                'foo' => (object) [
                    'type' => 'integer'
                ]
            ]
        ];

        $validator = $this->prophesize(Validator::class);
        $validator->reset()->willReturn(null);
        $validator->check($expectedQueryParams, $expectedJsonSchema)->willReturn(null);
        $validator->getErrors()->willReturn([]);

        $schema = $this->prophesize(Schema::class);
        $schema->getQueryParameters('/', 'GET')->willReturn($queryParamsInSchema);

        $requestValidator = new RequestValidator($schema->reveal(), $validator->reveal());
        $requestValidator->validateQueryString('foo=1234', '/', 'GET');
    }

    /**
     * @todo This test demonstrate that the getNormalizedQueryParams() method should be isolated somewhere else
     * @dataProvider getQueryParameters
     */
    public function testQueryParamsNormalization($key, $actualValue, $expectedValue)
    {
        $validator = $this->prophesize(Validator::class);
        $schema = $this->prophesize(Schema::class);
        $requestValidator = new RequestValidator($schema->reveal(), $validator->reveal());

        $schemaParameters = [
            (object) ['name' => 'an_integer', 'type' => 'integer'],
            (object) ['name' => 'a_number', 'type' => 'number'],
            (object) ['name' => 'a_boolean', 'type' => 'boolean'],
        ];

        $normalized = $requestValidator->getNormalizedQueryParams([$key => $actualValue], $schemaParameters);

        self::assertEquals($normalized[$key], $expectedValue);
    }

    public function getQueryParameters()
    {
        return [
            // description => [key, actual, expected]
            'with an integer' => ['an_integer', '123', 123],
            'with a number' => ['a_number', '12.15', 12.15],
            'with true given as a string' => ['a_boolean', 'true', true],
            'with true given as a numeric' => ['a_boolean', '1', true],
            'with false given as a string' => ['a_boolean', 'false', false],
            'with false given as a numeric string' => ['a_boolean', '0', false]
        ];
    }

    public function testValidateRequest()
    {
        $schema = (new SchemaLoader())->load(__DIR__.'/fixtures/petstore.json');
        $validator = new Validator();

        $request = new Request(
            'POST',
            'http://domain.tld/api/pets',
            ['Content-Type' => 'application/json'],
            '{"id": 1234, "name": "Doggy"}'
        );

        $requestValidator = new RequestValidator($schema, $validator);
        $requestValidator->validateRequest($request);
    }

}