<?php

use Codeception\Util\Stub;
use ITMH\Soap\Mapper;

/**
 * Class SoapClientTest
 *
 * ./vendor/bin/codecept run unit MapperTest.php
 */
class MapperTest extends \Codeception\TestCase\Test
{

    const CLASS_NAME = '\ITMH\Soap\Mapper';

    /**
     * Helper function for getting reflection of method
     *
     * @param string $name Method name
     *
     * @return \ReflectionMethod
     */
    protected static function getMethod($name)
    {
        $reflection = new ReflectionClass(self::CLASS_NAME);
        $method = $reflection->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Test for isComplex method
     *
     * ./vendor/bin/codecept run unit MapperTest.php:testIsComplex
     *
     * @param mixed $data     Method arguments
     * @param bool  $expected Expected result
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Mapper::isComplex
     * @dataProvider providerIsComplex
     */
    public function testIsComplex($data, $expected)
    {
        $method = self::getMethod('isComplex');
        $object = Stub::make(self::CLASS_NAME);
        self::assertEquals($expected, $method->invokeArgs($object, [$data]));
    }

    /**
     * DataProvider for testIsComplex
     *
     * @return array
     */
    public function providerIsComplex()
    {
        return [
            'when data is not array or object than return false' => [
                'string',
                false
            ],
            'when data is array than return true' => [
                [],
                true
            ],
            'when data is object than return true' => [
                new stdClass(),
                true
            ],
        ];
    }


    /**
     * Test for checkClassExistence method
     *
     * ./vendor/bin/codecept run unit MapperTest.php:testCheckClassExistenceWhenClassNotExistsThanThrowError
     *
     * @return void
     *
     * @covers \ITMH\Soap\Mapper::checkClassExistence
     */
    public function testCheckClassExistenceWhenClassNotExistsThanThrowError()
    {
        $method = self::getMethod('checkClassExistence');
        $object = Stub::make(self::CLASS_NAME);
        /* @var \ITMH\Soap\Client $object */

        $this->setExpectedException('\ITMH\Soap\Exception\InvalidClassMappingException');
        $method->invokeArgs($object, ['\\NotExistedNamespace\\NotExistedClass']);
    }

    /**
     * Test for checkClassExistence method
     *
     * ./vendor/bin/codecept run unit MapperTest.php:testCheckClassExistenceWhenClassExistsThanPass
     *
     * @return void
     *
     * @covers \ITMH\Soap\Mapper::checkClassExistence
     */
    public function testCheckClassExistenceWhenClassExistsThanPass()
    {
        $method = self::getMethod('checkClassExistence');
        $object = Stub::make(self::CLASS_NAME);
        /* @var \ITMH\Soap\Client $object */

        $method->invokeArgs($object, ['\DateTime']);
        self::addToAssertionCount(1);
    }

    /**
     * Test for isMapMethod method
     *
     * ./vendor/bin/codecept run unit MapperTest.php:testIsMapMethod
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Mapper::isMapMethod
     */
    public function testIsMapMethod()
    {
        $config = ['method' => []];

        $mapper = new Mapper();
        $mapper->setConfig($config);

        $method = self::getMethod('isMapMethod');
        self::assertTrue($method->invoke($mapper, 'method'));
        self::assertFalse($method->invoke($mapper, 'anotherMethod'));
    }

    /**
     * Test for getMethodConfigKey method
     *
     * ./vendor/bin/codecept run unit MapperTest.php:testGetMethodConfigKey
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Mapper::getMethodConfigKey
     */
    public function testGetMethodConfigKey()
    {
        $config = ['method' => ['key' => 'value']];

        $mapper = new Mapper();
        $mapper->setConfig($config);

        $method = self::getMethod('getMethodConfigKey');
        self::assertEquals('value', $method->invokeArgs($mapper, ['method', 'key']));
        self::assertEquals(null, $method->invokeArgs($mapper, ['method', 'anotherKey']));
    }

    /**
     * Test for unwrapItem method throws InvalidParameterException
     *
     * ./vendor/bin/codecept run unit MapperTest.php:testUnwrapItemWhenDataIsNotObjectThanThrowInvalidParameterException
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Mapper::unwrapItem
     */
    public function testUnwrapItemWhenDataIsNotObjectThanThrowInvalidParameterException()
    {
        $mapper = new Mapper();

        $method = self::getMethod('unwrapItem');
        self::setExpectedException('ITMH\Soap\Exception\InvalidParameterException');
        $method->invokeArgs($mapper, [null, 'Not object']);
    }

    /**
     * Test for unwrapItem method throws MissingItemException
     *
     * ./vendor/bin/codecept run unit MapperTest.php:testUnwrapItemWhenDataIsObjectAndObjectHasNotItemThanThrowMissingItemException
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Mapper::unwrapItem
     */
    public function testUnwrapItemWhenDataIsObjectAndObjectHasNotItemThanThrowMissingItemException()
    {
        $mapper = new Mapper();
        $data = (object)['item' => 'value'];

        $method = self::getMethod('unwrapItem');
        self::setExpectedException('ITMH\Soap\Exception\MissingItemException');
        $method->invokeArgs($mapper, ['anotherItem', $data]);

        self::assertEquals('value', $method->invokeArgs($mapper, ['item', $data]));
    }

    /**
     * Test for unwrapItem method success result
     *
     * ./vendor/bin/codecept run unit MapperTest.php:testUnwrapItemWhenDataIsObjectAndObjectHasItemThanReturnItem
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Mapper::unwrapItem
     */
    public function testUnwrapItemWhenDataIsObjectAndObjectHasItemThanReturnItem()
    {
        $mapper = new Mapper();
        $data = (object)['item' => 'value'];

        $method = self::getMethod('unwrapItem');
        self::assertEquals('value', $method->invokeArgs($mapper, ['item', $data]));
    }


    /**
     * Test for testMapMethodResponse method success result
     *
     * ./vendor/bin/codecept run unit MapperTest.php:testMapMethodResponse
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Mapper::mapMethodResponse
     */
    public function testMapMethodResponse()
    {
        $mapper = Stub::make(
            '\ITMH\Soap\Mapper',
            [
                'mapData' => Stub::atLeastOnce(),
                'unwrapItem' => Stub::atLeastOnce()
            ]
        );
        /* @var \ITMH\Soap\Mapper $mapper */

        self::assertEquals([], $mapper->mapMethodResponse('method', []));

        $mapper->setConfig(['method' => ['root' => 'ClassName']]);
        $mapper->mapMethodResponse('method', new stdClass());

        $mapper->setConfig(['method' => ['class' => 'ClassName']]);
        $mapper->mapMethodResponse('method', new stdClass());
    }
}
