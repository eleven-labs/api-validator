<?php
namespace ElevenLabs\Api\Validator;

use ElevenLabs\Api\Decoder\Adapter\SymfonyDecoderAdapter;
use ElevenLabs\Api\Definition\MessageDefinition;
use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\ResponseDefinition;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Serializer\Encoder\JsonDecode;

/**
 * Class RequestValidatorTest
 */
class MessageValidatorTest extends TestCase
{
    /** @var MessageValidator */
    private $messageValidator;

    public function setUp()
    {
        $validator = new Validator();
        $decoder = new SymfonyDecoderAdapter(new JsonDecode());

        $this->messageValidator = new MessageValidator(
            $validator,
            $decoder
        );
    }

    /** @test */
    public function itValidateAMessageContentType()
    {
        $expectedViolations = [
            new ConstraintViolation(
                'Content-Type',
                'Content-Type should not be empty',
                'required',
                'header'
            )
        ];

        $message = $this->prophesize(MessageInterface::class);
        $message->getHeaderLine('Content-Type')->willReturn('');

        $definition = $this->prophesize(MessageDefinition::class);
        $definition->getContentTypes()->willReturn(['application/json']);

        $this->messageValidator->validateContentType(
            $message->reveal(),
            $definition->reveal()
        );

        assertThat($this->messageValidator->hasViolations(), isTrue());
        assertThat($this->messageValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->messageValidator->getViolations(), equalTo($expectedViolations));
    }

    /** @test */
    public function itValidateAMessageUnsupportedContentType()
    {
        $expectedViolations = [
            new ConstraintViolation(
                'Content-Type',
                'text/plain is not a supported content type, supported: application/json',
                'enum',
                'header'
            )
        ];

        $message = $this->prophesize(MessageInterface::class);
        $message->getHeaderLine('Content-Type')->willReturn('text/plain');

        $definition = $this->prophesize(MessageDefinition::class);
        $definition->getContentTypes()->willReturn(['application/json']);

        $this->messageValidator->validateContentType(
            $message->reveal(),
            $definition->reveal()
        );

        assertThat($this->messageValidator->hasViolations(), isTrue());
        assertThat($this->messageValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->messageValidator->getViolations(), equalTo($expectedViolations));
    }

    /** @test */
    public function itValidateAMessageHeaders()
    {
        $expectedViolation = [
            new ConstraintViolation(
                'X-Required-Header',
                'The property X-Required-Header is required',
                'required',
                'header'
            )
        ];

        $headersSchema = $this->toObject([
            'type' => 'object',
            'required' => ['X-Required-Header'],
            'properties' => [
                'X-Required-Header' => [
                    'type' => 'string'
                ]
            ]
        ]);

        $message = $this->prophesize(MessageInterface::class);
        $message->getHeaders()->willReturn(['X-Foo' => ['bar', 'baz']]);

        $definition = $this->prophesize(MessageDefinition::class);
        $definition->hasHeadersSchema()->willReturn(true);
        $definition->getHeadersSchema()->willReturn($headersSchema);

        $this->messageValidator->validateHeaders(
            $message->reveal(),
            $definition->reveal()
        );

        assertThat($this->messageValidator->hasViolations(), isTrue());
        assertThat($this->messageValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->messageValidator->getViolations(), equalTo($expectedViolation));
    }

    /** @test */
    public function itValidateTheRequestBody()
    {
        $expectedViolation = [
            new ConstraintViolation(
                'id',
                'String value found, but an integer is required',
                'type',
                'body'
            ),
        ];

        $bodySchema = $this->toObject([
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'format' => 'int32'
                ]
            ]
        ]);

        $message = $this->prophesize(MessageInterface::class);
        $message->getHeaderLine('Content-Type')->willReturn('application/json');
        $message->getBody()->willReturn('{"id": "invalid"}');

        $definition = $this->prophesize(MessageDefinition::class);
        $definition->getContentTypes()->willReturn(['application/json']);
        $definition->hasBodySchema()->willReturn(true);
        $definition->getBodySchema()->willReturn($bodySchema);

        $this->messageValidator->validateMessageBody(
            $message->reveal(),
            $definition->reveal()
        );

        assertThat($this->messageValidator->hasViolations(), isTrue());
        assertThat($this->messageValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->messageValidator->getViolations(), equalTo($expectedViolation));
    }

    /** @test */
    public function itValidateARequestQueryParameters()
    {
        $expectedViolation = [
            new ConstraintViolation(
                'limit',
                'String value found, but an integer is required',
                'type',
                'query'
            )
        ];

        $queryParametersSchema = $this->toObject([
            'type' => 'object',
            'properties' => [
                'limit' => [
                    'type' => 'integer'
                ]
            ]
        ]);

        $requestUri = $this->prophesize(UriInterface::class);
        $requestUri->getQuery()->willreturn('limit=invalid');

        $request = $this->prophesize(RequestInterface::class);
        $request->getUri()->willReturn($requestUri);

        $definition = $this->prophesize(RequestDefinition::class);
        $definition->hasQueryParametersSchema()->willReturn(true);
        $definition->getQueryParametersSchema()->willReturn($queryParametersSchema);

        $this->messageValidator->validateQueryParameters(
            $request->reveal(),
            $definition->reveal()
        );

        assertThat($this->messageValidator->hasViolations(), isTrue());
        assertThat($this->messageValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->messageValidator->getViolations(), equalTo($expectedViolation));
    }

    /** @test */
    public function itValidateARequest()
    {
        $expectedViolations = [
            new ConstraintViolation('id', 'String value found, but an integer is required', 'type', 'body'),
            new ConstraintViolation('X-Required-Header', 'The property X-Required-Header is required', 'required', 'header'),
            new ConstraintViolation('limit', 'String value found, but an integer is required', 'type', 'query'),
        ];

        $headersSchema = $this->toObject([
            'type' => 'object',
            'required' => ['X-Required-Header'],
            'properties' => [
                'X-Required-Header' => [
                    'type' => 'string'
                ]
            ]
        ]);

        $bodySchema = $this->toObject([
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'format' => 'int32'
                ]
            ]
        ]);

        $queryParametersSchema = $this->toObject([
            'type' => 'object',
            'properties' => [
                'limit' => [
                    'type' => 'integer'
                ]
            ]
        ]);

        $uri = $this->prophesize(UriInterface::class);
        $uri->getQuery()->willreturn('limit=invalid');

        $request = $this->prophesize(RequestInterface::class);
        $request->getMethod()->willReturn('POST');
        $request->getUri()->willReturn($uri);
        $request->getBody()->willReturn('{"id": "invalid"}');
        $request->getHeaderLine('Content-Type')->willReturn('application/json');
        $request->getHeaders()->willReturn([]);

        $definition = $this->prophesize(RequestDefinition::class);
        $definition->getContentTypes()->willReturn(['application/json']);
        $definition->hasBodySchema()->willReturn(true);
        $definition->getBodySchema()->willReturn($bodySchema);
        $definition->hasHeadersSchema()->willReturn(true);
        $definition->getHeadersSchema()->willReturn($headersSchema);
        $definition->hasQueryParametersSchema()->willReturn(true);
        $definition->getQueryParametersSchema()->willReturn($queryParametersSchema);

        $this->messageValidator->validateRequest(
            $request->reveal(),
            $definition->reveal()
        );

        assertThat($this->messageValidator->hasViolations(), isTrue());
        assertThat($this->messageValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->messageValidator->getViolations(), equalTo($expectedViolations));
    }

    /** @test */
    public function itValidateAResponse()
    {
        $expectedViolations = [
            new ConstraintViolation('id', 'String value found, but an integer is required', 'type', 'body'),
            new ConstraintViolation('X-Required-Header', 'The property X-Required-Header is required', 'required', 'header'),
        ];

        $headersSchema = $this->toObject([
            'type' => 'object',
            'required' => ['X-Required-Header'],
            'properties' => [
                'X-Required-Header' => [
                    'type' => 'string'
                ]
            ]
        ]);

        $bodySchema = $this->toObject([
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'integer',
                    'format' => 'int32'
                ]
            ]
        ]);

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn('200');
        $response->getBody()->willReturn('{"id": "invalid"}');
        $response->getHeaderLine('Content-Type')->willReturn('application/json');
        $response->getHeaders()->willReturn([]);

        $responseDefinition = $this->prophesize(RequestDefinition::class);
        $responseDefinition->getContentTypes()->willReturn(['application/json']);
        $responseDefinition->hasBodySchema()->willReturn(true);
        $responseDefinition->getBodySchema()->willReturn($bodySchema);
        $responseDefinition->hasHeadersSchema()->willReturn(true);
        $responseDefinition->getHeadersSchema()->willReturn($headersSchema);

        $definition = $this->prophesize(RequestDefinition::class);
        $definition->getResponseDefinition('200')->willReturn($responseDefinition);

        $this->messageValidator->validateResponse(
            $response->reveal(),
            $definition->reveal()
        );

        assertThat($this->messageValidator->hasViolations(), isTrue());
        assertThat($this->messageValidator->getViolations(), containsOnlyInstancesOf(ConstraintViolation::class));
        assertThat($this->messageValidator->getViolations(), equalTo($expectedViolations));
    }

    private function toObject(array $array)
    {
        return json_decode(json_encode($array));
    }
}
