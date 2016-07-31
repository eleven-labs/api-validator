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
        $requestValidator->validateHeaders(['x-foo' => 'bar'], '/', 'GET');
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