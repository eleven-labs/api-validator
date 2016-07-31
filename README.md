# Swagger Request Validator

This library was made to validate PSR-7 Request against an OpenAPI/Swagger2.0 API definition.

It can consume `json` and `yaml` Schema files.

**Important** The library won't ensure that your schema is valid.

## Usage

```php
<?php
use ElevenLabs\Swagger\SchemaLoader;
use ElevenLabs\Swagger\RequestValidator;
use Symfony\Component\Config\ConfigCache;
use JsonSchema\Validator as JsonSchemaValidator;

$schemaLoader = new SchemaLoader(
    // the cache is optioal
    new ConfigCache('/path/to/your/cache/file.php', true)
);

$validator = new RequestValidator(
    $schemaLoader->load('/path/to/your/schema.json'),
    new JsonSchemaValidator()
);
```

### Validate a request

You can use any `$request` that implement the `\Psr\Http\Message\RequestInterface` interface.

```php
<?php
$validator->validateRequest($request);

if ($validator->hasViolations()) {
    $violations = $validator->getViolations();
}
```
