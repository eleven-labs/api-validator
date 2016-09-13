<?php
namespace ElevenLabs\Api\Factory;

use ElevenLabs\Api\Definition\RequestDefinition;
use ElevenLabs\Api\Definition\Parameter;
use ElevenLabs\Api\Definition\Parameters;
use ElevenLabs\Api\Definition\ResponseDefinition;
use ElevenLabs\Api\Schema;
use PHPUnit\Framework\TestCase;

class SwaggerSchemaFactoryTest extends TestCase
{
    /** @test */
    public function itShouldCreateASchemaFromAFile()
    {
        $schemaFile = 'file://'.__DIR__.'/../fixtures/petstore.json';
        $factory = new SwaggerSchemaFactory();
        $schema = $factory->createSchema($schemaFile);

        assertThat($schema, isInstanceOf(Schema::class));
    }
    /** @test */
    public function itShouldHaveSchemaProperties()
    {
        $schemaFile = 'file://'.__DIR__.'/../fixtures/petstore.json';
        $factory = new SwaggerSchemaFactory();
        $schema = $factory->createSchema($schemaFile);

        assertThat($schema->getHost(), equalTo('petstore.swagger.io'));
        assertThat($schema->getBasePath(), equalTo('/api'));
        assertThat($schema->getSchemes(), equalTo(['http']));
    }
    /** @test */
    public function itCanCreateARequestDefinition()
    {
        $schemaFile = 'file://'.__DIR__.'/../fixtures/petstore.json';
        $factory = new SwaggerSchemaFactory();
        $schema = $factory->createSchema($schemaFile);

        $requestDefinition = $schema->getRequestDefinition('findFood');

        assertThat($requestDefinition, isInstanceOf(RequestDefinition::class));
        assertThat($requestDefinition->getMethod(), equalTo('GET'));
        assertThat($requestDefinition->getOperationId(), equalTo('findFood'));
        assertThat($requestDefinition->getContentTypes(), equalTo(['application/json']));
        assertThat($requestDefinition->getPathTemplate(), equalTo('/api/food'));
        assertThat($requestDefinition->getRequestParameters(), isInstanceOf(Parameters::class));
        assertThat($requestDefinition->getResponseDefinition(304), isInstanceOf(ResponseDefinition::class));
    }

    /** @test */
    public function itCanCreateRequestParameters()
    {
        $schemaFile = 'file://'.__DIR__.'/../fixtures/petstore.json';
        $factory = new SwaggerSchemaFactory();
        $schema = $factory->createSchema($schemaFile);

        $requestParameters = $schema->getRequestDefinition('addPet')->getRequestParameters();

        assertThat($requestParameters, isInstanceOf(Parameters::class));
        assertThat($requestParameters->getBody(), isInstanceOf(Parameter::class));
        assertThat($requestParameters->getBodySchema(), isType('object'));

        $requestParameters = $schema->getRequestDefinition('findPetById')->getRequestParameters();

        assertThat($requestParameters->getPath(), containsOnlyInstancesOf(Parameter::class));

        $requestParameters = $schema->getRequestDefinition('findPets')->getRequestParameters();

        assertThat($requestParameters->getQuery(), containsOnlyInstancesOf(Parameter::class));
        assertThat($requestParameters->getQueryParametersSchema(), isType('object'));

        $requestParameters = $schema->getRequestDefinition('updatePet')->getRequestParameters();

        assertThat($requestParameters->getHeaders(), containsOnlyInstancesOf(Parameter::class));
        assertThat($requestParameters->getHeadersSchema(), isType('object'));
    }

    /** @test */
    public function itCanCreateAResponseDefinition()
    {
        $schemaFile = 'file://'.__DIR__.'/../fixtures/petstore.json';
        $factory = new SwaggerSchemaFactory();
        $schema = $factory->createSchema($schemaFile);

        $responseDefinition = $schema->getRequestDefinition('addPet')->getResponseDefinition(200);

        assertThat($responseDefinition, isInstanceOf(ResponseDefinition::class));
        assertThat($responseDefinition->getContentTypes(), contains('application/json'));
        assertThat($responseDefinition->getBodySchema(), isType('object'));
        assertThat($responseDefinition->getStatusCode(), equalTo(200));
    }
}