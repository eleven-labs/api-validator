<?php
namespace ElevenLabs\Swagger;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\FileResource;

class SchemaLoaderTest extends TestCase
{
    /**
     * @dataProvider getFilename
     */
    public function testLoad($filename)
    {
        $schema = (new SchemaLoader())->load($filename);

        self::assertInternalType('object', $schema);
    }

    public function getFilename()
    {
        return [
            'from a json file' => [__DIR__.'/fixtures/petstore.json'],
            'from a yaml file' => [__DIR__.'/fixtures/petstore.yml']
        ];
    }

    public function testLoadFromCache()
    {
        $cache = $this->prophesize(ConfigCacheInterface::class);
        $cache->isFresh()->shouldBeCalledTimes(1)->willReturn(true);
        $cache->getPath()->shouldBeCalledTimes(1)->willReturn(__DIR__.'/fixtures/petstore.php');

        $loader = new SchemaLoader($cache->reveal());

        self::assertInternalType('object', $loader->load(__DIR__.'/fixtures/petstore.json'));
    }

    public function testLoadCacheWrite()
    {
        $filename = __DIR__.'/fixtures/petstore.json';

        $cache = $this->prophesize(ConfigCacheInterface::class);
        $cache->isFresh()->shouldBeCalledTimes(1)->willReturn(false);
        $cache->write(Argument::type('string'), [new FileResource($filename)])->shouldBeCalledTimes(1);

        $loader = new SchemaLoader($cache->reveal());

        self::assertInternalType('object', $loader->load($filename));
    }

    public function testLoadUnsupportedFileThrowAnException()
    {
        $filename = __DIR__.'/fixtures/petstore.txt';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('file "'.$filename.'" does not provide a supported extension choose either json, yml or yaml');

        (new SchemaLoader())->load(__DIR__.'/fixtures/petstore.txt');
    }

}