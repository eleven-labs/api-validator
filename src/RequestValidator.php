<?php
namespace ElevenLabs\Swagger;

use JsonSchema\Validator;
use Psr\Http\Message\RequestInterface;
use ElevenLabs\Swagger\Exception\ConstraintViolations;

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
     * @var ConstraintViolation[]
     */
    private $violations;

    public function __construct(Schema $schema, Validator $validator)
    {
        $this->schema = $schema;
        $this->validator = $validator;
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
        $requestPath = $request->getUri()->getPath();
        $httpMethod = $request->getMethod();

        if ( ! $this->schema->findPathInTemplates($requestPath, $pathTemplate, $params) ) {
            throw new \InvalidArgumentException(sprintf('Unable to find "%s" in Swagger definition', $requestPath));
        }

        $this->validateMediaType($request->getHeaderLine('Content-Type'), $pathTemplate, $httpMethod);
        $this->validateHeaders($request->getHeaders(), $pathTemplate, $httpMethod);
        $this->validateQueryString($request->getUri()->getQuery(), $pathTemplate, $httpMethod);

        if (in_array($request->getMethod(), ['PUT', 'PATCH', 'POST'])) {
            $this->validateBodyString((string) $request->getBody(), $pathTemplate, $httpMethod);
        }
    }

    /**
     * Validate Request Body and return an array of errors
     *
     * @param string $bodyString A Request body string
     * @param string $pathTemplate A Schema path as described in Swagger
     * @param string $httpMethod An HTTP Method
     *
     * @return array
     */
    public function validateBodyString($bodyString, $pathTemplate, $httpMethod)
    {
        $bodySchema = $this->schema->getRequestSchema($pathTemplate, $httpMethod);

        $this->validateAgainstSchema(
            json_decode($bodyString),
            $bodySchema,
            'body'
        );
    }

    /**
     * Validate an array of request headers
     *
     * @param array $headers
     * @param string $pathTemplate
     * @param string $httpMethod
     */
    public function validateHeaders(array $headers, $pathTemplate, $httpMethod)
    {
        $schemaHeaders = $this->schema->getRequestHeadersParameters($pathTemplate, $httpMethod);

        $this->validateParameters(array_change_key_case($headers, CASE_LOWER), $schemaHeaders, 'header');
    }

    /**
     * Validate an array of Request query parameters
     *
     * @param string $queryString
     * @param string $pathTemplate
     * @param string $httpMethod
     */
    public function validateQueryString($queryString, $pathTemplate, $httpMethod)
    {
        parse_str($queryString, $queryParams);

        if (! empty($queryParams)) {
            $schemaQueryParams = $this->schema->getQueryParameters($pathTemplate, $httpMethod);
            $normalizedQueryParams = $this->getNormalizedQueryParams($queryParams, $schemaQueryParams);

            $this->validateParameters($normalizedQueryParams, $schemaQueryParams, 'query');
        }
    }


    /**
     * Validate the Request ContentType
     *
     * @param string $mediaType
     * @param string $pathTemplate
     * @param string $httpMethod
     */
    public function validateMediaType($mediaType, $pathTemplate, $httpMethod)
    {
        // Validate Request MediaType
        $allowedMediaTypes = $this->schema->getRequestMediaTypes($pathTemplate, $httpMethod);

        if (! in_array($mediaType, $allowedMediaTypes)) {
            $this->addViolation(
                new ConstraintViolation(
                    'content-type',
                    sprintf(
                        '%s is not an allowed media type, supported: %s',
                        $mediaType,
                        implode(',', $allowedMediaTypes)
                    ),
                    'required',
                    'header'
                )
            );
        }
    }

    private function validateParameters(array $params, array $schemaParams, $location)
    {
        $keys = [];
        $required = [];
        foreach ($schemaParams as &$schemaParam) {
            $keys[] = $schemaParam->name;
            if (isset($schemaParam->required) && $schemaParam->required === true) {
                $required[] = $schemaParam->name;
            }

            // remove unnecessary parameters
            unset($schemaParam->in);
            unset($schemaParam->name);
            unset($schemaParam->required);
        }

        $validationSchema = new \stdClass();
        $validationSchema->type = 'object';
        $validationSchema->required = $required;
        $validationSchema->properties = (object) array_combine($keys, $schemaParams);

        $this->validateAgainstSchema(
            (object) $params,
            $validationSchema,
            $location
        );
    }

    /**
     * Normalize parameters
     *
     * @param array $params
     * @param array $schemaParams
     *
     * @return array An array of query parameters with to right type
     */
    public function getNormalizedQueryParams(array $params, array $schemaParams)
    {
        foreach ($schemaParams as $schemaParam) {
            $name = $schemaParam->name;
            $type = isset($schemaParam->type) ? $schemaParam->type : 'string';
            if (array_key_exists($name, $params)) {
                switch ($type) {
                    case 'boolean':
                        if ($params[$name] === 'false') {
                            $params[$name] = false;
                        }
                        if ($params[$name] === 'true') {
                            $params[$name] = true;
                        }
                        if (in_array($params[$name], ['0', '1'])) {
                            $params[$name] = (bool) $params[$name];
                        }
                        break;
                    case 'integer':
                        $params[$name] = (int) $params[$name];
                        break;
                    case 'number':
                        $params[$name] = (float) $params[$name];
                        break;
                }
            }
        }

        return $params;
    }

    /**
     * Validate a value against a JSON Schema
     *
     * @param mixed $value
     * @param object $schema
     * @param string $location
     */
    private function validateAgainstSchema($value, $schema, $location)
    {
        $this->validator->reset();
        $this->validator->check($value, $schema);

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

    private function addViolation(ConstraintViolation $violation)
    {
        $this->violations[] = $violation;
    }

    /**
     * Indicate if we have violations in the pipe
     *
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
     * Return an exception containing an array of ConstraintViolation
     *
     * @return ConstraintViolations
     */
    public function getConstraintViolationsException()
    {
        return new ConstraintViolations($this->violations);
    }

}