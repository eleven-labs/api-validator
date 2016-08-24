<?php
namespace ElevenLabs\Api\Validator;

use ElevenLabs\Api\Decoder\DecoderInterface;
use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\RequestParameters;
use ElevenLabs\Api\Normalizer\QueryParamsNormalizer;
use ElevenLabs\Api\Schema;
use JsonSchema\Validator;
use Psr\Http\Message\RequestInterface;
use ElevenLabs\Api\Validator\Exception\ConstraintViolations;

/**
 * Validate a Request against the API Specification
 */
class RequestValidator
{
    /**
     * @var Schema
     */
    private $schema;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var DecoderInterface
     */
    private $decoder;

    /**
     * @var ConstraintViolation[]
     */
    private $violations;

    /**
     * @var bool
     */
    private $validContentType = true;

    public function __construct(Schema $schema, Validator $validator, DecoderInterface $decoder)
    {
        $this->schema = $schema;
        $this->validator = $validator;
        $this->decoder = $decoder;
    }

    /**
     * Validate a PSR7 Request Message
     *
     * @param RequestInterface $request
     *
     * @throws ConstraintViolations
     */
    public function validateRequest(RequestInterface $request)
    {
        $path = $request->getUri()->getPath();
        $method = $request->getMethod();
        $operationId = $this->schema->findOperationId($method, $path);
        $requestDefinition = $this->schema->getRequestDefinition($operationId);

        $requestParameters = $requestDefinition->getRequestParameters();

        $this->validateContentType($request, $requestDefinition);
        $this->validateQuery($request, $requestParameters);
        $this->validateHeaders($request, $requestParameters);
        $this->validateBody($request, $requestParameters);
    }

    private function validateQuery(RequestInterface $request, RequestParameters $requestParameters)
    {
        if (null !== $querySchema = $requestParameters->getQuerySchema()) {
            parse_str($request->getUri()->getQuery(), $queryParams);
            $queryParams = QueryParamsNormalizer::normalize($queryParams, $querySchema);

            $this->validate((object) $queryParams, $querySchema, 'query');
        }
    }

    private function validateHeaders(RequestInterface $request, RequestParameters $requestParameters)
    {
        if (null !== $headersSchema = $requestParameters->getHeadersSchema()) {
            // transform header values into a string
            $headers = array_map(
                function (array $values) {
                    return implode(', ', $values);
                },
                $request->getHeaders()
            );

            $this->validate((object) array_change_key_case($headers, CASE_LOWER), $headersSchema, 'header');
        }
    }

    private function validateContentType(RequestInterface $request, RequestDefinition $requestDefinition)
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (! in_array($contentType, $requestDefinition->getContentTypes())) {
            $this->validContentType = false;

            if ($contentType === '') {
                $message = 'Content-Type should not be empty';
                $constraint = 'required';
            } else {
                $message = sprintf(
                    '%s is not a supported content type, supported: %s',
                    $request->getHeaderLine('Content-Type'),
                    implode(', ', $requestDefinition->getContentTypes())
                );
                $constraint = 'enum';
            }

            $this->addViolation(
                new ConstraintViolation(
                    'Content-Type',
                    $message,
                    $constraint,
                    'header'
                )
            );
        }
    }

    private function validateBody(RequestInterface $request, RequestParameters $requestParameters)
    {
        if (
            (in_array($request->getMethod(), ['PUT', 'PATCH', 'POST'])) &&
            (null !== $bodySchema = $requestParameters->getBodySchema()) &&
            ($this->validContentType === true)
        ) {
            $format = $this->extractFormatFromContentType($request->getHeaderLine('Content-Type'));
            $decodedBody = $this->decoder->decode((string) $request->getBody(), $format);

            $this->validate($decodedBody, $bodySchema, 'body');
        }
    }

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

    private function validate($data, $schema, $location)
    {
        $this->validator->check($data, $schema);
        if (! $this->validator->isValid()) {

            $violations = array_map(
                function($error) use ($location) {
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

    private function addViolation(ConstraintViolation $violation)
    {
        $this->violations[] = $violation;
    }

    /**
     * Extract the format form the content type
     *
     * Examples:
     * - application/atom+xml => xml
     * - application/json => json
     *
     * @param $contentType
     *
     * @todo Put this utility method in its own class maybe ?
     *
     * @return mixed|string
     */
    private function extractFormatFromContentType($contentType)
    {
        $parts = explode('/', $contentType);
        $format = array_pop($parts);
        if (false !== $pos = strpos($format, '+')) {
            $format = substr($format, $pos+1);
        }

        return $format;
    }

}