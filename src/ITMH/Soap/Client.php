<?php

namespace ITMH\Soap;

use ITMH\Soap\Exception\ConnectionErrorException;
use ITMH\Soap\Exception\InvalidClassMappingException;
use ITMH\Soap\Exception\InvalidParameterException;
use ITMH\Soap\Exception\MissingClassMappingException;
use SoapClient;

/**
 * Soap Client
 *
 * @author Carlos Cima
 */
class Client extends SoapClient
{
    /**
     * Default Values
     */
    const DEFAULT_USER_AGENT = 'CamcimaSoapClient/1.0';
    const DEFAULT_CONTENT_TYPE = 'text/xml; charset=utf-8';
    const DEFAULT_PROXY_TYPE = CURLPROXY_HTTP;
    const DEFAULT_PROXY_HOST = 'localhost';
    const DEFAULT_PROXY_PORT = 8888;

    /**
     * Strict mapping mode
     *
     * @var bool
     */
    private $strictMapping = false;

    /**
     * Cookies
     *
     * @var array
     */
    protected $cookies = array();

    /**
     * User Agent
     *
     * @var string
     */
    protected $userAgent;

    /**
     * Content Type
     *
     * @var string
     */
    protected $contentType;

    /**
     * СURL Options
     *
     * @var array
     */
    protected $curlOptions;

    /**
     * Proxy Type
     *
     * @var int
     */
    protected $proxyType;

    /**
     * Proxy Host
     *
     * @var string
     */
    protected $proxyHost;

    /**
     * Proxy Port
     *
     * @var int
     */
    protected $proxyPort;

    /**
     * Lowercase first character of the root element name
     *
     * @var boolean
     */
    protected $lowerCaseFirst;

    /**
     * Keep empty object properties when building the request parameters
     *
     * @var boolean
     */
    protected $keepNullProperties;

    /**
     * Debug Mode
     *
     * @var boolean
     */
    protected $debug;

    /**
     * Communication Log of Last Request
     *
     * @var string
     */
    protected $communicationLog;

    /**
     * Original SoapClient Options
     *
     * @var array
     */
    protected $soapOptions;

    /**
     * Constructor
     *
     * @param string $wsdl    WSDL url
     * @param array  $options WSDL options
     */
    public function __construct($wsdl, array $options = array())
    {
        parent::__construct($wsdl, $options);
        $this->soapOptions = $options;
        $this->curlOptions = array();
        $this->lowerCaseFirst = false;
        $this->keepNullProperties = true;
        $this->debug = false;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $request  Request parameters
     * @param string $location URL of Soap Server
     * @param string $action   Soap method
     * @param int    $version  Version of SOAP protocol
     * @param int    $one_way  Unused variable
     *
     * @return mixed
     * @throws \ITMH\Soap\Exception\ConnectionErrorException
     * @throws \ITMH\Soap\Exception\InvalidParameterException
     * @throws \RuntimeException
     */
    public function __doRequest(
        $request,
        $location,
        $action,
        $version,
        $one_way = 0
    ) {
        $userAgent = $this->userAgent ?: self::DEFAULT_USER_AGENT;
        $contentType = $this->contentType ?: self::DEFAULT_CONTENT_TYPE;

        $headers = array(
            'Connection: Close',
            'User-Agent: ' . $userAgent,
            'Content-Type: ' . $contentType,
            'SOAPAction: "' . $action . '"',
            'Expect:'
        );

        $soapRequest = is_object($request)
            ? $this->getSoapVariables($request, $this->lowerCaseFirst, $this->keepNullProperties)
            : $request;

        $curlOptions = $this->getCurlOptions();
        $curlOptions[CURLOPT_POSTFIELDS] = $soapRequest;
        $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        $curlOptions[CURLINFO_HEADER_OUT] = true;
        $curlOptions[CURLOPT_COOKIE] = $this->parseCookies();

        if (isset($this->soapOptions['login'], $this->soapOptions['password'])) {
            $curlOptions[CURLOPT_USERPWD] = $this->soapOptions['login'] . ':' . $this->soapOptions['password'];
        }

        $curlHandler = curl_init($location);
        curl_setopt_array($curlHandler, $curlOptions);
        $requestDateTime = new \DateTime();
        try {
            $response = curl_exec($curlHandler);
        } catch (\Exception $exception) {
            throw new ConnectionErrorException(
                'Soap Connection Error: ' . $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        if (curl_errno($curlHandler)) {
            throw new ConnectionErrorException(
                'Soap Connection Error: ' . curl_error($curlHandler),
                curl_errno($curlHandler)
            );
        }

        if ($response === false) {
            throw new ConnectionErrorException(
                'Soap Connection Error: Empty Response'
            );
        }

        $requestMessage = curl_getinfo($curlHandler, CURLINFO_HEADER_OUT) . $soapRequest;
        $parsedResponse = $this->parseCurlResponse($response);
        if ($this->debug) {
            $this->logCurlMessage($requestMessage, $requestDateTime);
            $this->logCurlMessage($response, new \DateTime());
        }

        $this->communicationLog = $requestMessage . "\n\n" . $response;
        $body = $parsedResponse['body'];
        curl_close($curlHandler);

        return $body;
    }

    /**
     * Maps Result XML Elements to Classes
     *
     * @param \stdClass $soapResult           SOAP result
     * @param array     $resultClassMap       Result class map
     * @param string    $resultClassNamespace Result class namespace
     *
     * @return mixed
     * @throws \ITMH\Soap\Exception\InvalidParameterException
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     * @throws \ITMH\Soap\Exception\MissingClassMappingException
     * @throws \ReflectionException
     */
    public function asClass(
        $soapResult,
        array $resultClassMap = array(),
        $resultClassNamespace = ''
    ) {
        if (!is_object($soapResult)) {
            throw new InvalidParameterException('Soap Result is not an object');
        }

        $objVarsNames = array_keys(get_object_vars($soapResult));
        $rootClassName = reset($objVarsNames);
        $soapResultObj = $this->mapObject(
            $soapResult->$rootClassName,
            $rootClassName,
            $resultClassMap,
            $resultClassNamespace
        );

        return $soapResultObj;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $name  Cookie name
     * @param mixed  $value Cookie value
     *
     * @return void
     *
     * @codeCoverageIgnore Not contains logic
     */
    public function __setCookie($name, $value = null)
    {
        $this->cookies[$name] = $value;
    }

    /**
     * Parse the cookies into a valid HTTP Cookie header value
     *
     * @return string
     */
    protected function parseCookies()
    {
        $cookie = '';

        foreach ($this->cookies as $name => $value) {
            $cookie .= $name . '=' . $value . '; ';
        }

        return rtrim($cookie, '; ');
    }

    /**
     * Setter for strictMapping
     *
     * @param bool $strictMapping Strict mapping flag
     *
     * @return $this
     *
     * @codeCoverageIgnore Not contains logic
     */
    public function setStrictMapping($strictMapping = true)
    {
        $this->strictMapping = $strictMapping;

        return $this;
    }

    /**
     * Set cURL Options
     *
     * @param array $curlOptions CURL options
     *
     * @return self
     *
     * @codeCoverageIgnore Not contains logic
     */
    public function setCurlOptions(array $curlOptions)
    {
        $this->curlOptions = $curlOptions;

        return $this;
    }

    /**
     * Set User agent
     *
     * @param string $userAgent User agent
     *
     * @return self
     *
     * @codeCoverageIgnore Not contains logic
     */
    public function setUserAgent($userAgent = self::DEFAULT_USER_AGENT)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Set content type
     *
     * @param string $contentType Content type
     *
     * @return self
     *
     * @codeCoverageIgnore Not contains logic
     */
    public function setContentType($contentType = self::DEFAULT_CONTENT_TYPE)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Lowercase first character of the root element name
     *
     * Defaults to false
     *
     * @param bool $lowerCaseFirst Lower case flag
     *
     * @return self
     *
     * @codeCoverageIgnore Not contains logic
     */
    public function setLowerCaseFirst($lowerCaseFirst)
    {
        $this->lowerCaseFirst = $lowerCaseFirst;

        return $this;
    }

    /**
     * Keep null object properties when building the request parameters
     *
     * Defaults to true
     *
     * @param bool $keepNullProperties Null properties flag
     *
     * @return self
     *
     * @codeCoverageIgnore Not contains logic
     */
    public function setKeepNullProperties($keepNullProperties)
    {
        $this->keepNullProperties = $keepNullProperties;

        return $this;
    }

    /**
     * Set Debug Mode
     *
     * @param bool $debug Debug mode flag
     *
     * @return self
     *
     * @codeCoverageIgnore Not contains logic
     */
    public function setDebug($debug = true)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Use Proxy
     *
     * @param string $host Host
     * @param int    $port Port
     * @param int    $type Type
     *
     * @return self
     *
     * @codeCoverageIgnore Not contains logic
     */
    public function useProxy(
        $host = self::DEFAULT_PROXY_HOST,
        $port = self::DEFAULT_PROXY_PORT,
        $type = self::DEFAULT_PROXY_TYPE
    ) {
        $this->proxyType = $type;
        $this->proxyHost = $host;
        $this->proxyPort = $port;

        return $this;
    }

    /**
     * Merge Curl Options
     *
     * @return array
     */
    public function getCurlOptions()
    {
        $mandatoryOptions = array(
            CURLOPT_POST => true,
            CURLOPT_HEADER => true
        );

        $defaultOptions = array(
            CURLOPT_VERBOSE => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        );

        /* @noinspection AdditionOperationOnArraysInspection */
        $mergedArray = $mandatoryOptions + $this->curlOptions + $defaultOptions;

        if (strlen($this->proxyHost) > 0) {
            $proxyPort = self::DEFAULT_PROXY_PORT;
            if (strlen($this->proxyPort) > 0) {
                $proxyPort = $this->proxyPort;
            }

            $mergedArray[CURLOPT_PROXYTYPE] = $this->proxyType;
            $mergedArray[CURLOPT_PROXY] = $this->proxyHost;
            $mergedArray[CURLOPT_PROXYPORT] = $proxyPort;
        }

        return $mergedArray;
    }

    /**
     * Get SOAP Request Variables
     *
     * Prepares request parameters to be
     * sent in the SOAP Request Body.
     *
     * @param mixed $requestObject      Request object
     * @param bool  $lowerCaseFirst     Lowercase first character of the root element name
     * @param bool  $keepNullProperties Keep null object properties when building the request parameters
     *
     * @throws \ITMH\Soap\Exception\InvalidParameterException
     * @return array
     */
    public function getSoapVariables(
        $requestObject,
        $lowerCaseFirst = false,
        $keepNullProperties = true
    ) {
        if (!is_object($requestObject)) {
            throw new InvalidParameterException('Parameter requestObject is not an object');
        }

        $objectName = $this->getClassNameWithoutNamespaces($requestObject);
        if ($lowerCaseFirst) {
            $objectName = lcfirst($objectName);
        }

        $stdClass = new \stdClass();
        $stdClass->$objectName = $requestObject;

        return $this->objectToArray($stdClass, $keepNullProperties);
    }

    /**
     * Get Communication Log of Last Request
     *
     * @return string
     *
     * @codeCoverageIgnore Not contains logic
     */
    public function getCommunicationLog()
    {
        return $this->communicationLog;
    }

    /**
     * Get Class Without Namespace Information
     *
     * @param mixed $object Object
     *
     * @return string
     *
     * @codeCoverageIgnore Not contains logic
     */
    protected function getClassNameWithoutNamespaces($object)
    {
        $class = explode('\\', get_class($object));

        return end($class);
    }

    /**
     * Convert Object to Array
     *
     * This method omits null value properties
     *
     * @param mixed   $obj                Object or array for converting
     * @param boolean $keepNullProperties Keep null object properties when building the request parameters
     *
     * @return array
     */
    protected function objectToArray($obj, $keepNullProperties = true)
    {
        $arr = array();
        $arrObj = $this->getObjectVars($obj);

        foreach ($arrObj as $key => $val) {
            $val = $this->isComplex($val)
                ? $this->objectToArray($val, $keepNullProperties)
                : $val;

            if ($keepNullProperties || $val !== null) {
                $val = ($val === null) ? '' : $val;
                $arr[$key] = $val;
            }
        }

        return $arr;
    }

    /**
     * Wrapper for function get_object_vars
     *
     * @param mixed $argument Argument
     *
     * @return array
     */
    protected function getObjectVars($argument)
    {
        return is_object($argument)
            ? get_object_vars($argument)
            : $argument;
    }

    /**
     * Map response as array
     *
     * @param mixed $obj           Response instance
     * @param bool  $asStrictArray Strict flag
     *
     * @return mixed
     */
    public function asArray($obj, $asStrictArray = false)
    {
        $array = $this->objectToArray($obj);

        return $asStrictArray ? $array : reset($array);
    }

    /**
     * Map Remote SOAP Objects(stdClass) to local classes
     *
     * @param mixed  $obj            Remote SOAP Object
     * @param string $className      Root (or current) class name
     * @param array  $classMap       Class Mapping
     * @param string $classNamespace Namespace where the local classes are located
     *
     * @return mixed
     * @throws \ITMH\Soap\Exception\MissingClassMappingException
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     * @throws \ReflectionException
     */
    protected function mapObject(
        $obj,
        $className,
        $classMap = array(),
        $classNamespace = ''
    ) {

        if (!$this->isComplex($obj)) {
            return $obj;
        }

        if (is_array($obj)) {
            codecept_debug($obj);

            return $this->mapArray($obj, $className, $classMap, $classNamespace);
        }

        // Check if there is a mapping.
        $mappedClassName = $this->getMappedClassName($className, $classMap, $classNamespace);

        // Get class properties and methods.
        $objProperties = array_keys(get_class_vars($mappedClassName));
        $objMethods = get_class_methods($mappedClassName);

        // Instantiate new mapped object.
        $objInstance = new $mappedClassName();

        // Map remote object to local object.
        $arrObj = get_object_vars($obj);
        $propertiesMap = $this->getPropertyMap($objInstance);
        foreach ($arrObj as $key => $val) {
            if ($val !== null) {
                if (array_key_exists($key, $propertiesMap)) {
                    $key = $propertiesMap[$key];
                }

                $useSetter = $this->hasSetter($key, $objMethods);
                if (!$useSetter && $this->strictMapping === true && !$this->hasProperty($key, $objProperties)) {
                    throw new InvalidClassMappingException('Property "' . $mappedClassName . '::' . $key . '" doesn\'t exist');
                }

                // If it's not scalar, recursive call the mapping function
                if ($this->isComplex($val)) {
                    $val = $this->mapObject($val, $key, $classMap, $classNamespace);
                }

                // If there is a setter, use it. If not, set the property directly.
                if ($useSetter) {
                    $this->mapPropertyWithSetter($objInstance, $key, $val, $mappedClassName);
                } else {
                    $this->mapProperty($objInstance, $key, $val);
                }
            }
        }

        return $objInstance;
    }

    /**
     * Map Remote SOAP response to local classes if response is array
     *
     * @param mixed  $data           Remote SOAP Object
     * @param string $className      Root (or current) class name
     * @param array  $classMap       Class Mapping
     * @param string $classNamespace Namespace where the local classes are located
     *
     * @return array
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     * @throws \ITMH\Soap\Exception\MissingClassMappingException
     * @throws \ReflectionException
     */
    protected function mapArray($data, $className, $classMap, $classNamespace = '')
    {
        // If array mapping exists, map array elements.
        $className = 'array|' . $className;
        if (!array_key_exists($className, $classMap)) {
            return $data;
        }

        $returnArray = [];
        foreach ($data as $key => $val) {
            $returnArray[$key] = $this->mapObject($val, $className, $classMap, $classNamespace);
        }

        return $returnArray;
    }

    /**
     * Get mapped class name
     *
     * @param string $className      Root (or current) class name
     * @param array  $classMap       Class Mapping
     * @param string $classNamespace Namespace where the local classes are located
     *
     * @return mixed
     * @throws \ITMH\Soap\Exception\MissingClassMappingException
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     */
    protected function getMappedClassName($className, $classMap, $classNamespace = null)
    {
        if (array_key_exists($className, $classMap)) {
            $mappedClassName = $classMap[$className];
        } else {
            if (!$classNamespace) {
                throw new MissingClassMappingException('Missing mapping for element "' . $className . '"');
            }

            $mappedClassName = str_replace('\\\\', '\\', $classNamespace . '\\' . $className);
        }

        // Existence
        $this->checkClassExistence($className);

        return $mappedClassName;
    }

    /**
     * Check class existence
     *
     * @param string $className Class name
     *
     * @return void
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     */
    protected function checkClassExistence($className)
    {
        if (!class_exists($className)) {
            throw new InvalidClassMappingException('Class not found: "' . $className . '"');
        }
    }

    /**
     * Check if object has setter method for corresponding property
     *
     * @param string $attribute     Attrubute name
     * @param array  $objectMethods Object methods list
     *
     * @return bool
     */
    protected function hasSetter($attribute, $objectMethods)
    {
        return in_array($this->getSetterName($attribute), $objectMethods, true);
    }

    /**
     * Check if object has property
     *
     * @param string $attribute        Attrubute name
     * @param array  $objectProperties Object properties list
     *
     * @return bool
     */
    protected function hasProperty($attribute, $objectProperties)
    {
        return in_array($attribute, $objectProperties, true);
    }

    /**
     * Check if object is not scalar type
     *
     * @param mixed $variable Scalar or not scalar variable
     *
     * @return bool
     */
    protected function isComplex($variable)
    {
        return is_array($variable) || is_object($variable);
    }

    /**
     * Return custom property map for given object
     *
     * @param mixed $object Object instance
     *
     * @return array
     */
    protected function getPropertyMap($object)
    {
        return $object instanceof MappingInterface ? $object->getMap() : [];
    }

    /**
     * Assign value to property
     *
     * @param mixed  $object Instance of object
     * @param string $key    Property name
     * @param mixed  $value  Value
     *
     * @return void
     *
     * @codeCoverageIgnore Not contains logic
     */
    protected function mapProperty($object, $key, $value)
    {
        $object->$key = $value;
    }

    /**
     * Map property to object with setter
     *
     * @param mixed  $object          Object for mapping
     * @param string $key             Attribute name
     * @param mixed  $value           Attribute value
     * @param string $mappedClassName Class name of object
     *
     * @return void
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     * @throws \ReflectionException
     *
     * @codeCoverageIgnore Not contains logic
     */
    protected function mapPropertyWithSetter($object, $key, $value, $mappedClassName)
    {
        $setterName = $this->getSetterName($key);
        $value = $this->castParameter($mappedClassName, $key, $value);
        $object->$setterName($value);
    }

    /**
     * Cast the value, if parameter is typehinted
     *
     * @param string $mappedClassName Object class name
     * @param string $key             Attribute name
     * @param mixed  $value           Value
     *
     * @return \DateTime
     * @throws \ReflectionException
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     * @todo   Make extensible
     */
    protected function castParameter($mappedClassName, $key, $value)
    {
        $parameter = $this->getSetterParameter($mappedClassName, $key);
        $parameterClass = $this->getParameterClass($parameter);
        if ($parameterClass) {
            $paramClassName = $parameterClass->getNamespaceName() . '\\' . $parameterClass->getName();
            if ($paramClassName === '\DateTime') {
                $value = new \DateTime($value);
            }
        }

        return $value;
    }

    /**
     * Return
     *
     * @param string $mappedClassName Object class name
     * @param string $key             Attribute
     *
     * @return \ReflectionParameter
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     */
    protected function getSetterParameter($mappedClassName, $key)
    {
        $reflection = $this->getMethodReflection($mappedClassName, $this->getSetterName($key));
        $params = $reflection->getParameters();
        if (count($params) !== 1) {
            throw new InvalidClassMappingException('Wrong Argument Count in Setter for property ' . $key);
        }

        return reset($params);
    }


    /**
     * Return reflection of method
     *
     * @param string $className  Class name
     * @param string $methodName Method name
     *
     * @return \ReflectionMethod
     *
     * @codeCoverageIgnore Not contains logic
     */
    protected function getMethodReflection($className, $methodName)
    {
        return new \ReflectionMethod($className, $this->getSetterName($methodName));
    }

    /**
     * Get setter name
     *
     * @param string $key Attribute name
     *
     * @return string
     *
     * @codeCoverageIgnore Not contains logic
     */
    protected function getSetterName($key)
    {
        return 'set' . $key;
    }

    /**
     * Get the parameter class (if type-hinted)
     *
     * @param \ReflectionParameter $param Setter parameter
     *
     * @return \ReflectionClass
     * @throws \ReflectionException
     *
     * @link http://php.net/manual/en/reflectionparameter.getclass.php method not documented.
     *       I can't reproduce exception.
     */
    protected function getParameterClass($param)
    {
        try {
            $paramClass = $param->getClass();
        } catch (\ReflectionException $exception) {
            throw new \ReflectionException('Invalid type hint for method "' . $param->getDeclaringFunction() . '"');
        }

        return $paramClass;
    }

    /**
     * Parse cURL response into header and body
     *
     * Inspired by shuber cURL wrapper.
     *
     * @param string $response Response
     *
     * @return array
     */
    protected function parseCurlResponse($response)
    {
        $pattern = '|HTTP/\d\.\d.*?$.*?\r\n\r\n|ims';
        preg_match_all($pattern, $response, $matches);
        $header = array_pop($matches[0]);
        // Remove headers from the response body
        $body = str_replace($header, '', $response);

        return [
            'header' => $header,
            'body' => $body
        ];
    }

    /**
     * Log cURL Debug Message
     *
     * @param string    $message          Debug message
     * @param \DateTime $messageTimestamp Timestamp
     *
     * @return void
     * @throws \RuntimeException
     *
     * @codeCoverageIgnore Not contains logic
     */
    protected function logCurlMessage($message, \DateTime $messageTimestamp)
    {
        error_log('[' . $messageTimestamp->format('Y-m-d H:i:s') . ']' . $message . "\n\n");
    }
}
