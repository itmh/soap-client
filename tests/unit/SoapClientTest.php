<?php

use Codeception\Util\Stub;

/**
 * Class SoapClientTest
 *
 * ./vendor/bin/codecept run unit SoapClientTest.php
 */
class SoapClientTest extends \Codeception\TestCase\Test
{

    const CLASS_NAME = '\ITMH\Soap\Client';

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


    public function testGetQuoteFromWebServiceX()
    {
        $client = new \ITMH\Soap\Client(
            'http://www.webservicex.net/StockQuote.asmx?WSDL',
            []
        );
        $result = $client->GetQuote([
            'symbol' => 'USD'
        ]);
        self::assertTrue($result !== false);
    }

    /**
     * Test for isComplex method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testIsComplex
     *
     * @param mixed $data     Method arguments
     * @param bool  $expected Expected result
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::isComplex
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
     * Test for getPropertyMap method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testGetPropertyMap
     *
     * @param array $data     Method arguments
     * @param array $expected Expected result
     *
     * @return void
     *
     * @dataProvider providerGetPropertyMap
     * @covers       \ITMH\Soap\Client::getPropertyMap
     */
    public function testGetPropertyMap($data, $expected)
    {
        $method = self::getMethod('getPropertyMap');
        $object = Stub::make(self::CLASS_NAME);
        self::assertEquals($expected, $method->invokeArgs($object, $data));
    }

    /**
     * DataProvider for testGetPropertyMap
     *
     * @return array
     */
    public function providerGetPropertyMap()
    {
        $mapper = $this->getMock('\\ITMH\\Soap\\MappableInterface');
        $mapper->method('getMap')->willReturn(['foo' => 'bar']);
        /* @var \ITMH\Soap\MappableInterface $mapper */

        return [
            'when object is instance of MappnigInterface than return getMap' => [
                [$mapper],
                $mapper->getMap()
            ],
            'when object is not instance of MappingInterface than return empty array' => [
                [new stdClass()],
                []
            ]
        ];
    }

    /**
     * Test for hasProperty method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testHasProperty
     *
     * @param array $data     Method arguments
     * @param bool  $expected Expected result
     *
     * @return void
     *
     * @dataProvider providerHasProperty
     * @covers       \ITMH\Soap\Client::hasProperty
     */
    public function testHasProperty($data, $expected)
    {
        $method = self::getMethod('hasProperty');
        $object = Stub::make(self::CLASS_NAME);
        self::assertEquals($expected, $method->invokeArgs($object, $data));
    }

    /**
     * DataProvider for testHasProperty
     *
     * @return array
     */
    public function providerHasProperty()
    {
        return [
            'when array has key than return true' => [
                ['first', ['first', 'second']],
                true
            ],
            'when array hasn\'t key than return false' => [
                ['first', ['second']],
                false
            ],
        ];
    }

    /**
     * Test for hasSetter method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testHasSetter
     *
     * @param array $data     Method arguments
     * @param bool  $expected Expected result
     *
     * @return void
     *
     * @dataProvider providerHasSetter
     * @covers       \ITMH\Soap\Client::hasSetter
     */
    public function testHasSetter($data, $expected)
    {
        $method = self::getMethod('hasSetter');
        $object = Stub::make(self::CLASS_NAME);
        self::assertEquals($expected, $method->invokeArgs($object, $data));
    }

    /**
     * DataProvider for testHasSetter
     *
     * @return array
     */
    public function providerHasSetter()
    {
        return [
            'when array has key with set prefix than return true' => [
                ['First', ['setFirst', 'setSecond']],
                true
            ],
            'when array hasn\'t key with set prefix than return false' => [
                ['First', ['First', 'setSecond']],
                false
            ],
        ];
    }

    /**
     * Test for getMappedClassName method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testGetMappedClassName
     *
     * @param array $data     Method arguments
     * @param bool  $expected Expected result
     *
     * @return void
     *
     * @dataProvider providerGetMappedClassName
     * @covers       \ITMH\Soap\Client::getMappedClassName
     */
    public function testGetMappedClassName($data, $expected)
    {
        $method = self::getMethod('getMappedClassName');
        $object = Stub::make(
            self::CLASS_NAME,
            ['checkClassExistence' => null]
        );
        self::assertEquals($expected, $method->invokeArgs($object, $data));
    }

    /**
     * Data provider for testHasSetter
     *
     * @return array
     */
    public function providerGetMappedClassName()
    {
        return [
            'when class in classmap than return mapped class' => [
                ['SomeClass', ['SomeClass' => 'SomeOtherClass'], 'Namespace'],
                'SomeOtherClass'
            ],
            'when class is not in classmap than return class concatenated with namespace' => [
                ['SomeClass', [], 'Namespace'],
                'stdClass'
            ],
        ];
    }

    /**
     * Test for getMappedClassName
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testGetMappedClassNameException
     *
     * @return void
     *
     * @covers \ITMH\Soap\Client::getMappedClassName
     */
    public function testGetMappedClassNameException()
    {
        $method = self::getMethod('getMappedClassName');
        $object = Stub::make(self::CLASS_NAME);

        $data = ['SomeClass', ['SomeClass' => 'NotExistedClass']];
        self::setExpectedException('\ITMH\Soap\Exception\InvalidClassMappingException');
        $method->invokeArgs($object, $data);
    }


    /**
     * Test for asArray method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testAsArray
     *
     * @param array $array    objectToArray result
     * @param bool  $strict   Strict flag
     * @param array $expected Expected result
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::asArray
     * @dataProvider providerAsArray
     */
    public function testAsArray($array, $strict, $expected)
    {
        /* @var \ITMH\Soap\Client $object */
        $object = Stub::make(
            self::CLASS_NAME,
            ['objectToArray' => $array]
        );
        $result = $object->asArray(new stdClass(), $strict);
        self::assertEquals($expected, $result);
    }

    /**
     * Data provider for testAsArray
     *
     * @return array
     */
    public function providerAsArray()
    {
        return [
            'when strict than return array as is' => [
                ['key' => 'value'],
                true,
                ['key' => 'value']
            ],
            'when not strict than return array first key' => [
                ['key' => 'value'],
                false,
                'value'
            ],
        ];
    }

    /**
     * Test for parseCookies method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testParseCookies
     *
     * @param array  $cookies  Cookies array
     * @param string $expected Expected result
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::parseCookies
     * @dataProvider providerParseCookies
     */
    public function testParseCookies($cookies, $expected)
    {
        $method = self::getMethod('parseCookies');
        $object = Stub::make(
            self::CLASS_NAME,
            ['cookies' => $cookies]
        );

        self::assertEquals($expected, $method->invoke($object));
    }

    /**
     * Data provider for testParseCookies
     *
     * @return array
     */
    public function providerParseCookies()
    {
        return [
            'when cookies is empty than return empty string' => [
                [],
                ''
            ],
            'when cookies is not empty than return concatenated cookies' => [
                ['foo' => 'bar'],
                'foo=bar'
            ]
        ];
    }

    /**
     * Test for method getCurlOptions
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testGetCurlOptions
     *
     * @param array $attributes Client proxy attributes
     * @param array $expected   Expected result
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::getCurlOptions
     * @dataProvider providerGetCurlOptions
     */
    public function testGetCurlOptions($attributes, $expected)
    {
        $method = self::getMethod('getCurlOptions');
        $object = Stub::make(
            self::CLASS_NAME,
            $attributes
        );

        self::assertEquals($expected, $method->invoke($object));
    }


    /**
     * Data provider for getCurlOptions
     *
     * @return array
     */
    public function providerGetCurlOptions()
    {
        return [
            'when proxy host is omitted than return config without proxy' => [
                [
                    'curlOptions' => []
                ],
                [
                    CURLOPT_POST => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_VERBOSE => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false
                ]
            ],
            'when proxy host is setted than return config with proxy' => [
                [
                    'curlOptions' => [],
                    'proxyType' => 'type',
                    'proxyHost' => 'host'
                ],
                [
                    CURLOPT_POST => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_VERBOSE => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_PROXYTYPE => 'type',
                    CURLOPT_PROXY => 'host',
                    CURLOPT_PROXYPORT => \ITMH\Soap\Client::DEFAULT_PROXY_PORT
                ]
            ],
            'when proxy host is setted and proxy port is setted than return config with proxy and port' => [
                [
                    'curlOptions' => [],
                    'proxyType' => 'type',
                    'proxyHost' => 'host',
                    'proxyPort' => 1337
                ],
                [
                    CURLOPT_POST => true,
                    CURLOPT_HEADER => true,
                    CURLOPT_VERBOSE => false,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_PROXYTYPE => 'type',
                    CURLOPT_PROXY => 'host',
                    CURLOPT_PROXYPORT => 1337
                ]
            ]
        ];
    }


    /**
     * Test for asClass method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testAsClass
     *
     * @return void
     *
     * @covers \ITMH\Soap\Client::asClass
     */
    public function testAsClass()
    {
        $object = Stub::make(
            self::CLASS_NAME,
            ['mapObject' => Stub::once()],
            $this
        );
        /* @var \ITMH\Soap\Client $object */
        $data = ['root' => (object)['foo' => 'bar']];
        $object->asClass((object)$data);
    }

    /**
     * Test for asClass exception throwing
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testAsClassException
     *
     * @return void
     *
     * @covers \ITMH\Soap\Client::asClass
     */
    public function testAsClassException()
    {
        $object = Stub::make(self::CLASS_NAME);
        /* @var \ITMH\Soap\Client $object */
        $this->setExpectedException('ITMH\Soap\Exception\InvalidParameterException');
        /* @noinspection PhpParamsInspection */
        $object->asClass('string');
    }

    /**
     * Test for getSetterParameter method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testGetSetterParameterWhenNotOneArgumentThanThrowException
     *
     * @return void
     *
     * @covers \ITMH\Soap\Client::getSetterParameter
     */
    public function testGetSetterParameterWhenNotOneArgumentThanThrowException()
    {
        $reflectionMethod = $this->getMock('stdClass', ['getParameters']);
        $reflectionMethod->method('getParameters')->willReturn([1, 2]);

        $method = self::getMethod('getSetterParameter');
        $object = Stub::make(
            self::CLASS_NAME,
            ['getMethodReflection' => $reflectionMethod]
        );

        $args = ['Foo', 'multiArgs'];
        self::setExpectedException('ITMH\Soap\Exception\InvalidClassMappingException');
        $method->invokeArgs($object, $args);
    }

    /**
     * Test for getSetterParameter method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testGetSetterParameterWhenOneArgumentThanPass
     *
     * @return void
     *
     * @covers \ITMH\Soap\Client::getSetterParameter
     */
    public function testGetSetterParameterWhenOneArgumentThanPass()
    {
        $reflectionMethod = $this->getMock('stdClass', ['getParameters']);
        $reflectionMethod->method('getParameters')->willReturn([1]);

        $method = self::getMethod('getSetterParameter');
        $object = Stub::make(
            self::CLASS_NAME,
            ['getMethodReflection' => $reflectionMethod]
        );

        $args = ['Foo', 'oneArgument'];
        $method->invokeArgs($object, $args);
        self::addToAssertionCount(1);
    }

    /**
     * Test for getSoapVariables
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testGetSoapVariablesWhenIsNotObjectThanThrowException
     *
     * @return void
     *
     * @covers \ITMH\Soap\Client::getSoapVariables
     */
    public function testGetSoapVariablesWhenIsNotObjectThanThrowException()
    {
        $object = Stub::make(self::CLASS_NAME);
        /* @var \ITMH\Soap\Client $object */
        self::setExpectedException('\ITMH\Soap\Exception\InvalidParameterException');
        $object->getSoapVariables('not object');
    }

    /**
     * Test for getSoapVariables
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testGetSoapVariables
     *
     * @param array $args Method arguments
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::getSoapVariables
     * @dataProvider providerGetSoapVariables
     */
    public function testGetSoapVariables($args)
    {
        $method = self::getMethod('getSoapVariables');
        $object = Stub::make(self::CLASS_NAME);
        /* @var \ITMH\Soap\Client $object */

        $method->invokeArgs($object, $args);
        self::addToAssertionCount(1);
    }

    /**
     * Data provider for testGetSoapVariables
     *
     * @return array
     */
    public function providerGetSoapVariables()
    {
        return [
            'when lowerCaseFirst is true than pass' => [[new DateTime(), true]],
            'when lowerCaseFirst is false than pass' => [[new DateTime(), false]]
        ];
    }

    /**
     * Test for getObjectVars
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testGetObjectVars
     *
     * @param array $args     Method arguments
     * @param mixed $expected Expected result
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::getObjectVars
     * @dataProvider providerGetObjectVars
     */
    public function testGetObjectVars($args, $expected)
    {
        $method = self::getMethod('getObjectVars');
        $object = Stub::make(self::CLASS_NAME);
        /* @var \ITMH\Soap\Client $object */

        self::assertEquals($expected, $method->invokeArgs($object, $args));
    }

    /**
     * Data provider for testObjectToArray
     *
     * @return array
     */
    public function providerGetObjectVars()
    {
        $data = ['first' => 1];

        return [
            'when argument is object than return object vars' => [
                [(object)$data],
                $data
            ],
            'when argument is not than return argument' => [
                ['not object'],
                'not object'
            ]
        ];
    }

    /**
     * Test for objectToArray
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testObjectToArray
     *
     * @param array $args Method arguments
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::objectToArray
     * @dataProvider providerObjectToArray
     */
    public function testObjectToArray($args)
    {
        $method = self::getMethod('objectToArray');
        $object = Stub::make(self::CLASS_NAME);
        /* @var \ITMH\Soap\Client $object */

        $method->invokeArgs($object, $args);
        self::addToAssertionCount(1);
    }

    /**
     * Data provider for testObjectToArray
     *
     * @return array
     */
    public function providerObjectToArray()
    {
        return [
            'when val is complex and keepNullProperties is false than pass' => [
                [[['foo']], false]
            ],
            'when val is not complex and keepNullProperties is false than pass' => [
                [['bar'], false]
            ],
            'when val is null and keepNullProperties is true than pass' => [
                [[null], true],
            ],
        ];
    }

    /**
     * Test for mapArray method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testMapArray
     *
     * @param array $args     Method arguments
     * @param mixed $expected Expected result
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::mapArray
     * @dataProvider providerMapArray
     */
    public function testMapArray($args, $expected)
    {
        $method = self::getMethod('mapArray');
        $object = Stub::make(
            self::CLASS_NAME,
            ['mapObject' => null]
        );
        /* @var \ITMH\Soap\Client $object */

        self::assertEquals($expected, $method->invokeArgs($object, $args));
    }

    /**
     * Data provider for testMapArray
     *
     * @return array
     */
    public function providerMapArray()
    {
        $data = new DateTime();

        return [
            'when key is not exists than return data' => [
                [$data, '\DateTime', []],
                $data
            ],
            'when key exists than pass' => [
                [[$data], '\DateTime', ['array|\DateTime' => '']],
                [null]
            ],
            'when key exists and data is empty array than pass' => [
                [[], '\DateTime', ['array|\DateTime' => '']],
                []
            ],
        ];
    }

    /**
     * Test for mapObject method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testMapObject
     *
     * @param array $args     Method arguments
     * @param array $params   Stub params
     * @param mixed $expected Expected result
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::mapObject
     * @dataProvider providerMapObject
     */
    public function testMapObject($args, $params, $expected)
    {
        $method = self::getMethod('mapObject');
        $object = Stub::make(
            self::CLASS_NAME,
            $params
        );
        /* @var \ITMH\Soap\Client $object */

        self::assertEquals($expected, $method->invokeArgs($object, $args));
    }

    /**
     * Data provider for testMapObject
     *
     * @return array
     */
    public function providerMapObject()
    {
        $params = [
            'mapArray' => [],
            'getMappedClassName' => '\\stdClass'
        ];

        return [
            'when object is not complex' => [
                ['not complex', 'Foo', [], ''],
                $params,
                'not complex'
            ],
            'when object is array than return mapArray' => [
                [[], 'Foo', [], ''],
                $params,
                []
            ],
            'when object without properties than return object instance' => [
                [new stdClass(), '\\stdClass', [], ''],
                $params,
                new stdClass()
            ],
            'when object with null property than return object instance' => [
                [(object)['property' => null], '\\stdClass', [], ''],
                $params,
                new stdClass()
            ],
            'when object with not null property than return object instance' => [
                [(object)['property' => 'foo'], '\\stdClass', [], ''],
                $params,
                (object)['property' => 'foo']
            ],
            'when object with not null property and exists in map than return object instance' => [
                [(object)['property' => 'foo'], '\\stdClass', [], ''],
                array_merge($params, ['getPropertyMap' => ['property' => 'bar']]),
                (object)['bar' => 'foo']
            ],
            'when object with not null property and value is complex than return object instance' => [
                [(object)['property' => (object)['foo' => 'bar']], '\\stdClass', [], ''],
                $params,
                (object)['property' => (object)['foo' => 'bar']]
            ],
            'when object with not null property and value is complex  than return object instance' => [
                [(object)['property' => 'bar'], '\\stdClass', [], ''],
                array_merge(
                    $params,
                    [
                        'hasSetter' => 'true',
                        'mapPropertyWithSetter' => null
                    ]
                ),
                new stdClass()
            ],
        ];
    }

    /**
     * Test for mapObject method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testMapObjectWhenStrictMappingAndNotHasSetterAndNotHasPropertyThanThrowError
     *
     * @return void
     *
     * @covers \ITMH\Soap\Client::mapObject
     */
    public function testMapObjectWhenStrictMappingAndNotHasSetterAndNotHasPropertyThanThrowError()
    {
        $method = self::getMethod('mapObject');
        $object = Stub::make(
            self::CLASS_NAME,
            [
                'hasSetter' => false,
                'hasProperty' => false,
                'strictMapping' => true
            ]
        );
        /* @var \ITMH\Soap\Client $object */

        $args = [
            (object)['foo' => 'bar'],
            'stdClass',
            ['stdClass' => 'stdClass']
        ];

        self::setExpectedException('\ITMH\Soap\Exception\InvalidClassMappingException');
        $method->invokeArgs($object, $args);
    }

    /**
     * Test for checkClassExistence method
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testCheckClassExistenceWhenClassNotExistsThanThrowError
     *
     * @return void
     *
     * @covers \ITMH\Soap\Client::checkClassExistence
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
     * ./vendor/bin/codecept run unit SoapClientTest.php:testCheckClassExistenceWhenClassExistsThanPass
     *
     * @return void
     *
     * @covers \ITMH\Soap\Client::checkClassExistence
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
     * Test for method castParameter
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testCastParameter
     *
     * @param array $args     Method attributes
     * @param array $params   Stub params
     * @param array $expected Expected result
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::castParameter
     * @dataProvider providerCastParameter
     */
    public function testCastParameter($args, $params, $expected)
    {
        $method = self::getMethod('castParameter');
        $object = Stub::make(
            self::CLASS_NAME,
            $params
        );

        self::assertEquals($expected, $method->invokeArgs($object, $args));
    }

    /**
     * Data provider for testCastParameter
     *
     * @return array
     */
    public function providerCastParameter()
    {
        $params = ['getSetterParameter' => null];

        $datetimeMock = $this->getMock('stdClass', ['getNamespaceName', 'getName']);
        $datetimeMock->method('getNamespaceName')->willReturn('');
        $datetimeMock->method('getName')->willReturn('DateTime');

        $mock = $this->getMock('stdClass', ['getNamespaceName', 'getName']);
        $mock->method('getNamespaceName')->willReturn('');
        $mock->method('getName')->willReturn('');

        return [
            'when parameter class is null than return value' => [
                ['foo', 'bar', 'baz'],
                array_merge(
                    $params,
                    ['getParameterClass' => null]
                ),
                'baz'
            ],

            'when parameter class is not null and class is not datetime than return value' => [
                ['foo', 'bar', 'baz'],
                array_merge(
                    $params,
                    [
                        'getSetterParameter' => true,
                        'getParameterClass' => $mock
                    ]
                ),
                'baz'
            ],
            'when parameter class is not null and class is datetime than return datetime' => [
                ['foo', 'bar', '2012-01-01'],
                array_merge(
                    $params,
                    [
                        'getSetterParameter' => true,
                        'getParameterClass' => $datetimeMock
                    ]
                ),
                new DateTime('2012-01-01')
            ]
        ];
    }

    /**
     * Test for method parseCurlResponse
     *
     * ./vendor/bin/codecept run unit SoapClientTest.php:testParseCurlResponse
     *
     * @param array $expected Expected result
     * @param array $args     Method attributes
     *
     * @return void
     *
     * @covers       \ITMH\Soap\Client::parseCurlResponse
     * @dataProvider providerParseCurlResponse
     */
    public function testParseCurlResponse($expected, $args)
    {
        $method = self::getMethod('parseCurlResponse');
        $client = $this->getMockBuilder(self::CLASS_NAME)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        self::assertEquals($expected, $method->invokeArgs($client, [$args]));
    }

    /**
     * Data provider for testParseCurlResponse
     *
     * @return array
     */
    public function providerParseCurlResponse()
    {
        return [
            'when body contains HTTP header then separate header and body' => [
                [
                    'header' => "HTTP/1.1 200 OK\r\nCache-Control: private, max-age=0\r\nContent-Length: 61936\r\n\r\n",
                    'body'   => "<?xml version=\"1.0\" encoding=\"utf-8\"?><soap:Envelope xmlns:soap=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"><soap:Body><DemandHistoryResponse xmlns=\"http://api.ris.itmh.local/B2CRegress/\"><Mem> 46.48.112.193 - - [01/Sep/2013:15:08:47 +0200] \"POST wp-login.php HTTP/1.0\" 301 555 \"referer-domain.tld\" \"Mozilla/5.0 (Windows NT 6.1; rv:19.0) Gecko/20130101 Firefox/19.0\"\r\n\r\n</Mem></DemandHistoryResponse></soap:Body></soap:Envelope>"
                ],
                "HTTP/1.1 200 OK\r\nCache-Control: private, max-age=0\r\nContent-Length: 61936\r\n\r\n<?xml version=\"1.0\" encoding=\"utf-8\"?><soap:Envelope xmlns:soap=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\"><soap:Body><DemandHistoryResponse xmlns=\"http://api.ris.itmh.local/B2CRegress/\"><Mem> 46.48.112.193 - - [01/Sep/2013:15:08:47 +0200] \"POST wp-login.php HTTP/1.0\" 301 555 \"referer-domain.tld\" \"Mozilla/5.0 (Windows NT 6.1; rv:19.0) Gecko/20130101 Firefox/19.0\"\r\n\r\n</Mem></DemandHistoryResponse></soap:Body></soap:Envelope>"
            ]
        ];
    }
}
