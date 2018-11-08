<?php
namespace ElevenLabs\Api\Validator;

use ElevenLabs\Api\Decoder\DecoderInterface;
use ElevenLabs\Api\Decoder\DecoderUtils;
use ElevenLabs\Api\Definition\MessageDefinition;
use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Normalizer\QueryParamsNormalizer;
use JsonSchema\Validator;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Provide validation methods to validate HTTP messages
 */
class MessageValidator
{
    /** @var Validator */
    private $validator;
    /** @var array */
    private $violations = [];
    /** @var DecoderInterface */
    private $decoder;

    public function __construct(Validator $validator, DecoderInterface $decoder)
    {
        $this->validator = $validator;
        $this->decoder = $decoder;
    }

    public function validateRequest(RequestInterface $request, RequestDefinition $definition)
    {
        $contentTypeValid = $this->validateContentType($request, $definition);
        if ($contentTypeValid && in_array($request->getMethod(), ['PUT', 'PATCH', 'POST'])) {
            $this->validateMessageBody($request, $definition);
        }
        $this->validateHeaders($request, $definition);
        $this->validateQueryParameters($request, $definition);
    }

    public function validateResponse(ResponseInterface $response, RequestDefinition $definition)
    {
        $responseDefinition = $definition->getResponseDefinition($response->getStatusCode());
        $contentTypeValid = $this->validateContentType($response, $responseDefinition);
        if ($contentTypeValid) {
            $this->validateMessageBody($response, $responseDefinition);
        }
        $this->validateHeaders($response, $responseDefinition);
    }

    public function validateMessageBody(MessageInterface $message, MessageDefinition $definition)
    {
        if ($message instanceof ServerRequestInterface) {
            $bodyString = json_encode((array) $message->getParsedBody());
        } else {
            $bodyString = (string) $message->getBody();
        }
        if ($bodyString !== '' && $definition->hasBodySchema()) {
            $contentType = $message->getHeaderLine('Content-Type');
            $decodedBody = $this->decoder->decode(
                $bodyString,
                DecoderUtils::extractFormatFromContentType($contentType)
            );

            $this->validate($decodedBody, $definition->getBodySchema(), 'body');
        }
    }

    public function validateHeaders(MessageInterface $message, MessageDefinition $definition)
    {
        if ($definition->hasHeadersSchema()) {
            // Transform each header values into a string
            $headers = array_map(
                function (array $values) {
                    return implode(', ', $values);
                },
                $message->getHeaders()
            );

            $this->validate(
                (object) array_change_key_case($headers, CASE_LOWER),
                $definition->getHeadersSchema(),
                'header'
            );
        }
    }

    /**
     * Validate an HTTP message content-type against a message definition
     *
     * @param MessageInterface $message
     * @param MessageDefinition $definition
     *
     * @return bool Is valid
     */
    public function validateContentType(MessageInterface $message, MessageDefinition $definition)
    {
        $isValid = true;
        $contentType = $message->getHeaderLine('Content-Type');

        if (! in_array($contentType, $definition->getContentTypes())) {
            $isValid = false;

            if ($contentType === '') {
                $violationMessage = 'Content-Type should not be empty';
                $constraint = 'required';
            } else {
                $violationMessage = sprintf(
                    '%s is not a supported content type, supported: %s',
                    $message->getHeaderLine('Content-Type'),
                    implode(', ', $definition->getContentTypes())
                );
                $constraint = 'enum';
            }

            $this->addViolation(
                new ConstraintViolation(
                    'Content-Type',
                    $violationMessage,
                    $constraint,
                    'header'
                )
            );
        }

        return $isValid;
    }

    public function validateQueryParameters(RequestInterface $request, RequestDefinition $definition)
    {
        if ($definition->hasQueryParametersSchema()) {
            parse_str($request->getUri()->getQuery(), $queryParams);
            $schema = $definition->getQueryParametersSchema();
            $queryParams = QueryParamsNormalizer::normalize($queryParams, $schema);

            $this->validate(
                (object) $queryParams,
                $schema,
                'query'
            );
        }
    }

    /**
     * @return bool
     */
    public function hasViolations()
    {
        return (!empty($this->violations));
    }

    /**
     * @return ConstraintViolation[]
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @param mixed $data
     * @param \stdClass $schema
     * @param string $location (possible values: query, path, body, headers)
     */
    protected function validate($data, $schema, $location)
    {
        $this->validator->check($data, $schema);
        if (! $this->validator->isValid()) {
            $violations = array_map(
                function ($error) use ($location) {
                    return new ConstraintViolation(
                        $error['property'],
                        $error['message'],
                        $error['constraint'],
                        $location
                    );
                },
                $this->validator->getErrors()
            );

            foreach ($violations as $violation) {
                $this->addViolation($violation);
            }
        }

        $this->validator->reset();
    }

    protected function addViolation(ConstraintViolation $violation)
    {
        $this->violations[] = $violation;
    }
}
