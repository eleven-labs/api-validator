# API Validator

This library provide a set of classes suited to describe a WebService based on the HTTP protocol.

It can validate [PSR-7 Requests](http://www.php-fig.org/psr/psr-7/) against a schema.

It's design is heavily inspired by the OpenAPI/Swagger2.0 specifications.

As of now it only support the OpenAPi/Swagger2.0 specifications but we plan to 
support [RAML 1.0](https://github.com/raml-org/raml-spec/blob/master/versions/raml-10/raml-10.md/) 
and [API Elements (API Blueprint)](https://github.com/apiaryio/api-elements) in the future.

## Dependencies

We rely on the [justinrainbow/json-schema](https://github.com/justinrainbow/json-schema) library 
to parse specification files and to validate request's `headers`, `query`, `uri` and `body` parts.

## Usage

### Before you start

You will need to write a **valid** Swagger 2.0 file in order to use this library. Ensure that this file is valid using 
the [Swagger Editor](http://editor.swagger.io/). 

You can although validate your specifications 
using the [Swagger 2.0 JSONSchema](https://github.com/OAI/OpenAPI-Specification/blob/master/schemas/v2.0/schema.json).

### Validate a request

You can validate any PSR-7 Request implementing the `Psr\Http\Message\RequestInterface` interface.

```php
<?php

use ElevenLabs\Api\Factory\SwaggerSchemaFactory;
use ElevenLabs\Api\Decoder\Adapter\SymfonyDecoderAdapter;
use ElevenLabs\Api\Validator\RequestValidator;
use JsonSchema\Validator;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\ChainDecoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use GuzzleHttp\Psr7\Request;

// Given a $request implementing the `Psr\Http\Message\RequestInterface`
// Here we are using the PSR7 Guzzle implementation;
$request = new Request(
    'POST', 
    'http://domain.tld/api/pets',
    ['application/json'],
    '{"id": 1, "name": "puppy"}'
);

$validator = new Validator();

// Here we are using decoders provided by the symfony serializer component
// feel free to use yours if you se desire. You just need to create an adapter that 
// implement the `ElevenLabs\Api\Decoder\DecoderInterface` 
$decoder = new SymfonyDecoderAdapter(
    new ChainDecoder([
        new JsonDecode(),
        new XmlEncoder()
    ])  
);

// Load a JSON swagger 2.0 schema using the SwaggerSchemaFactory class.
// We plan to support RAML 1.0 and APi Elements (API Blueprint) in the future.
$schema = (new SwaggerSchemaFactory())->createSchema('file://path/to/your/swagger.json');

$requestValidator = new RequestValidator($schema,$validator,$decoder);
$requestValidator->validateRequest($request);

// Check if the request has violations
if ($requestValidator->hasViolations()) {
    // Get violations and do something with them
    $violations = $requestValidator->getViolations();
}
```

### Working with Symfony HTTPFoundation Requests

You will need an adapter in order to valide symfony request.

We recommend you to use the [symfony/psr-http-message-bridge](https://github.com/symfony/psr-http-message-bridge)

### Using the schema

You can navigate the `ElevenLabs\Api\Schema` to meed other use cases.

Example:

```php
<?php
use ElevenLabs\Api\Factory\SwaggerSchemaFactory;

$schema = (new SwaggerSchemaFactory())->createSchema('file://path/to/your/swagger.json');

// Find a request definition from an HTTP method and a path.
$requestDefinition = $schema->getRequestDefinition(
    $schema->findOperationId('GET', '/pets/1234')
);

// Get the response definition for the status code 200 (HTTP OK)
$responseDefinition = $requestDefinition->getResponseDefinition(200);

// From here, you can access the JSON Schema describing the expected response
$responseSchema = $responseDefinition->getSchema();
```




