<?php
namespace ElevenLabs\Api\Factory;

use ElevenLabs\Api\Schema;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\equalTo;
use function PHPUnit\Framework\isInstanceOf;

class CachedSchemaFactoryDecoratorTest extends TestCase
{
    /** @test */
    public function itShouldSaveASchemaInACacheStore()
    {
        $schemaFile = 'file://fake-schema.yml';
        $schema = $this->prophesize(Schema::class);

        $item = $this->prophesize(CacheItemInterface::class);
        $item->isHit()->shouldBeCalled()->willReturn(false);
        $item->set($schema)->shouldBeCalled()->willReturn($item);

        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $cache->getItem('3f470a326a5926a2e323aaadd767c0e64302a080')->willReturn($item);
        $cache->save($item)->willReturn(true);

        $schemaFactory = $this->prophesize(SchemaFactory::class);
        $schemaFactory->createSchema($schemaFile)->willReturn($schema);

        $cachedSchema = new CachedSchemaFactoryDecorator(
            $cache->reveal(),
            $schemaFactory->reveal()
        );

        $expectedSchema = $schema->reveal();
        $actualSchema = $cachedSchema->createSchema($schemaFile);

        assertThat($actualSchema, isInstanceOf(Schema::class));
        assertThat($actualSchema, equalTo($expectedSchema));
    }

    /** @test */
    public function itShouldLoadASchemaFromACacheStore()
    {
        $schemaFile = 'file://fake-schema.yml';
        $schema = $this->prophesize(Schema::class);

        $item = $this->prophesize(CacheItemInterface::class);
        $item->isHit()->shouldBeCalled()->willReturn(true);
        $item->get()->shouldBeCalled()->willReturn($schema);

        $cache = $this->prophesize(CacheItemPoolInterface::class);
        $cache->getItem('3f470a326a5926a2e323aaadd767c0e64302a080')->willReturn($item);

        $schemaFactory = $this->prophesize(SchemaFactory::class);
        $schemaFactory->createSchema(Argument::any())->shouldNotBeCalled();

        $cachedSchema = new CachedSchemaFactoryDecorator(
            $cache->reveal(),
            $schemaFactory->reveal()
        );

        $expectedSchema = $schema->reveal();
        $actualSchema = $cachedSchema->createSchema($schemaFile);

        assertThat($actualSchema, isInstanceOf(Schema::class));
        assertThat($actualSchema, equalTo($expectedSchema));
    }
}
