<?php
namespace ElevenLabs\Api\Factory;

use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\Parameter;
use ElevenLabs\Api\Definition\Parameters;
use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Schema;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\containsOnlyInstancesOf;
use function PHPUnit\Framework\equalTo;
use function PHPUnit\Framework\isFalse;
use function PHPUnit\Framework\isInstanceOf;
use function PHPUnit\Framework\isTrue;
use function PHPUnit\Framework\isType;

class SwaggerSchemaFactoryTest extends TestCase
{
    /** @test */
    public function itCanCreateASchemaFromAJsonFile()
    {
        $schema = $this->getPetStoreSchemaJson();

        assertThat($schema, isInstanceOf(Schema::class));
    }

    /** @test */
    public function itCanCreateASchemaFromAYamlFile()
    {
        $schema = $this->getPetStoreSchemaYaml();

        assertThat($schema, isInstanceOf(Schema::class));
    }

    /** @test */
    public function itThrowAnExceptionWhenTheSchemaFileIsNotSupported()
    {
        $unsupportedFile = 'file://'.dirname(__DIR__).'/fixtures/petstore.txt';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/does not provide a supported extension/');

        (new SwaggerSchemaFactory())->createSchema($unsupportedFile);
    }

    /** @test */
    public function itShouldHaveSchemaProperties()
    {
        $schema = $this->getPetStoreSchemaJson();

        assertThat($schema->getHost(), equalTo('petstore.swagger.io'));
        assertThat($schema->getBasePath(), equalTo('/v2'));
        assertThat($schema->getSchemes(), equalTo(['https', 'http']));
    }

    /** @test */
    public function itThrowAnExceptionWhenAnOperationDoesNotProvideAnId()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You need to provide an operationId for GET /something');

        $this->getSchemaFromFile('operation-without-an-id.json');
    }

    /** @test */
    public function itThrowAnExceptionWhenAnOperationDoesNotProvideResponses()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('You need to specify at least one response for GET /something');

        $this->getSchemaFromFile('operation-without-responses.json');
    }

    /** @test */
    public function itSupportAnOperationWithoutParameters()
    {
        $schema = $this->getSchemaFromFile('operation-without-parameters.json');
        $definition = $schema->getRequestDefinition('getSomething');

        assertThat($definition->hasHeadersSchema(), isFalse());
        assertThat($definition->hasBodySchema(), isFalse());
        assertThat($definition->hasQueryParametersSchema(), isFalse());
    }

    /** @test */
    public function itCanCreateARequestDefinition()
    {
        $schema = $this->getPetStoreSchemaJson();

        $requestDefinition = $schema->getRequestDefinition('findPetsByStatus');

        assertThat($requestDefinition, isInstanceOf(RequestDefinition::class));
        assertThat($requestDefinition->getMethod(), equalTo('GET'));
        assertThat($requestDefinition->getOperationId(), equalTo('findPetsByStatus'));
        assertThat($requestDefinition->getPathTemplate(), equalTo('/v2/pet/findByStatus'));
        assertThat($requestDefinition->getContentTypes(), equalTo([]));
        assertThat($requestDefinition->getRequestParameters(), isInstanceOf(Parameters::class));
        assertThat($requestDefinition->getResponseDefinition(200), isInstanceOf(ResponseDefinition::class));
        assertThat($requestDefinition->getResponseDefinition(400), isInstanceOf(ResponseDefinition::class));
    }

    /** @test */
    public function itCanCreateARequestBodyParameter()
    {
        $schema = $this->getPetStoreSchemaJson();

        $requestParameters = $schema->getRequestDefinition('addPet')->getRequestParameters();

        assertThat($requestParameters, isInstanceOf(Parameters::class));
        assertThat($requestParameters->getBody(), isInstanceOf(Parameter::class));
        assertThat($requestParameters->hasBodySchema(), isTrue());
        assertThat($requestParameters->getBodySchema(), isType('object'));
    }

    /** @test */
    public function itCanCreateRequestPath()
    {
        $schema = $this->getPetStoreSchemaJson();

        $requestParameters = $schema->getRequestDefinition('getPetById')->getRequestParameters();

        assertThat($requestParameters->getPath(), containsOnlyInstancesOf(Parameter::class));
    }

    /** @test */
    public function itCanCreateRequestQueryParameters()
    {
        $schema = $this->getPetStoreSchemaJson();

        $requestParameters = $schema->getRequestDefinition('findPetsByStatus')->getRequestParameters();

        assertThat($requestParameters->getQuery(), containsOnlyInstancesOf(Parameter::class));
        assertThat($requestParameters->getQueryParametersSchema(), isType('object'));
    }

    /** @test */
    public function itCanCreateRequestHeadersParameter()
    {
        $schema = $this->getPetStoreSchemaJson();

        $requestParameters = $schema->getRequestDefinition('deletePet')->getRequestParameters();

        assertThat($requestParameters->getHeaders(), containsOnlyInstancesOf(Parameter::class));
        assertThat($requestParameters->hasHeadersSchema(), isTrue());
        assertThat($requestParameters->getHeadersSchema(), isType('object'));
    }

    /** @test */
    public function itCanCreateAResponseDefinition()
    {
        $schema = $this->getPetStoreSchemaJson();

        $responseDefinition = $schema->getRequestDefinition('getPetById')->getResponseDefinition(200);

        assertThat($responseDefinition, isInstanceOf(ResponseDefinition::class));
        assertThat($responseDefinition->getBodySchema(), isType('object'));
        assertThat($responseDefinition->getStatusCode(), equalTo(200));
        assertContains('application/json', $responseDefinition->getContentTypes());
    }

    public function itUseTheSchemaDefaultConsumesPropertyWhenNotProvidedByAnOperation()
    {
        $schema = $this->getSchemaFromFile('schema-with-default-consumes-and-produces-properties.json');
        $definition = $schema->getRequestDefinition('postSomething');

        assertContains('application/json', $definition->getContentTypes());
    }

    /** @test */
    public function itUseTheSchemaDefaultProducesPropertyWhenNotProvidedByAnOperationResponse()
    {
        $schema = $this->getSchemaFromFile('schema-with-default-consumes-and-produces-properties.json');
        $responseDefinition = $schema
            ->getRequestDefinition('postSomething')
            ->getResponseDefinition(201);

        assertContains('application/json', $responseDefinition->getContentTypes());
    }

    /**
     * @test
     * @dataProvider getGuessableContentTypes
     */
    public function itGuessTheContentTypeFromRequestParameters($operationId, $expectedContentType)
    {
        $schema = $this->getSchemaFromFile('request-without-content-types.json');

        $definition = $schema->getRequestDefinition($operationId);

        assertContains($expectedContentType, $definition->getContentTypes());
    }

    public function getGuessableContentTypes()
    {
        return [
            'body' => [
                'operationId' => 'postBodyWithoutAContentType',
                'contentType' => 'application/json'
            ],
            'formData' => [
                'operationId' => 'postFromDataWithoutAContentType',
                'contentType' => 'application/x-www-form-urlencoded'
            ],
        ];
    }

    /** @test */
    public function itFailWhenTryingToGuessTheContentTypeFromARequestWithMultipleBodyLocations()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Parameters cannot have body and formData locations ' .
            'at the same time in /post/with-conflicting-locations'
        );

        $schemaFile = 'file://'.dirname(__DIR__).'/fixtures/request-with-conflicting-locations.json';
        (new SwaggerSchemaFactory())->createSchema($schemaFile);
    }

    /**
     * @return Schema
     */
    private function getPetStoreSchemaJson()
    {
        return $this->getSchemaFromFile('petstore.json');
    }

    /**
     * @return Schema
     */
    private function getPetStoreSchemaYaml()
    {
        return $this->getSchemaFromFile('petstore.yaml');
    }

    /**
     * @param $name
     *
     * @return Schema
     */
    private function getSchemaFromFile($name)
    {
        $schemaFile = 'file://' . dirname(__DIR__) . '/fixtures/'.$name;
        $factory = new SwaggerSchemaFactory();

        return $factory->createSchema($schemaFile);
    }
}
