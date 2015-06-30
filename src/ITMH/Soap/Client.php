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
     * cURL Options
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
     * @param string $wsdl
     * @param array  $options
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

        $soapRequest = is_object($request) ?
            $this->getSoapVariables($request, $this->lowerCaseFirst,
                $this->keepNullProperties) :
            $request;

        $curlOptions = $this->getCurlOptions();
        $curlOptions[CURLOPT_POSTFIELDS] = $soapRequest;
        $curlOptions[CURLOPT_HTTPHEADER] = $headers;
        $curlOptions[CURLINFO_HEADER_OUT] = true;
        $curlOptions[CURLOPT_COOKIE] = $this->parseCookies();

        if (isset($this->soapOptions['login'], $this->soapOptions['password'])) {
            $curlOptions[CURLOPT_USERPWD] = $this->soapOptions['login'] . ':' . $this->soapOptions['password'];
        }
        $ch = curl_init($location);
        curl_setopt_array($ch, $curlOptions);
        $requestDateTime = new \DateTime();
        try {
            $response = curl_exec($ch);
        } catch (\Exception $e) {
            throw new ConnectionErrorException(
                'Soap Connection Error: ' . $e->getMessage(),
                $e->getCode(), $e
            );
        }
        if (curl_errno($ch)) {
            throw new ConnectionErrorException(
                'Soap Connection Error: ' . curl_error($ch),
                curl_errno($ch)
            );
        }
        if ($response === false) {
            throw new ConnectionErrorException(
                'Soap Connection Error: Empty Response'
            );
        }
        $requestMessage = curl_getinfo($ch, CURLINFO_HEADER_OUT) . $soapRequest;
        $parsedResponse = $this->parseCurlResponse($response);
        if ($this->debug) {
            $this->logCurlMessage($requestMessage, $requestDateTime);
            $this->logCurlMessage($response, new \DateTime());
        }
        $this->communicationLog = $requestMessage . "\n\n" . $response;
        $body = $parsedResponse['body'];
        curl_close($ch);

        return $body;
    }

    /**
     * Maps Result XML Elements to Classes
     *
     * @param \stdClass $soapResult
     * @param           $rootClassName
     * @param array     $resultClassMap
     * @param string    $resultClassNamespace
     *
     * @throws \ITMH\Soap\Exception\InvalidParameterException
     * @return object
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

        return rtrim($cookie, ';');
    }

    /**
     * @param bool $strictMapping
     *
     * @return $this
     */
    public function setStrictMapping($strictMapping = true)
    {
        $this->strictMapping = $strictMapping;

        return $this;
    }

    /**
     * Set cURL Options
     *
     * @param array $curlOptions
     *
     * @return self
     */
    public function setCurlOptions(array $curlOptions)
    {
        $this->curlOptions = $curlOptions;

        return $this;
    }

    /**
     * Set User Agent
     *
     * @param string $userAgent
     *
     * @return self
     */
    public function setUserAgent($userAgent = self::DEFAULT_USER_AGENT)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Set Content Type
     *
     * @param string $contentType
     *
     * @return self
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
     * @param boolean $lowerCaseFirst
     *
     * @return self
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
     * @param boolean $keepNullProperties
     *
     * @return self
     */
    public function setKeepNullProperties($keepNullProperties)
    {
        $this->keepNullProperties = $keepNullProperties;

        return $this;
    }

    /**
     * Set Debug Mode
     *
     * @param boolean $debug
     *
     * @return self
     */
    public function setDebug($debug = true)
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * Use Proxy
     *
     * @param string $host
     * @param int    $port
     * @param int    $type
     *
     * @return self
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

        /** @noinspection AdditionOperationOnArraysInspection */
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
     * @param object  $requestObject
     * @param boolean $lowerCaseFirst     Lowercase first character of the root element name
     * @param boolean $keepNullProperties Keep null object properties when building the request parameters
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
     */
    public function getCommunicationLog()
    {
        return $this->communicationLog;
    }

    /**
     * Get Class Without Namespace Information
     *
     * @param mixed $object
     *
     * @return string
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
     * @param mixed   $obj
     * @param boolean $keepNullProperties Keep null object properties when building the request parameters
     *
     * @return array
     */
    protected function objectToArray($obj, $keepNullProperties = true)
    {
        $arr = array();
        $arrObj = is_object($obj) ? get_object_vars($obj) : $obj;
        foreach ($arrObj as $key => $val) {
            $val = (is_array($val) || is_object($val)) ? $this->objectToArray($val,
                $keepNullProperties) : $val;
            if ($keepNullProperties || $val !== null) {
                $val = ($val === null) ? $val = '' : $val;
                $arr[$key] = is_scalar($val) ? ((string)$val) : $val;
            }
        }

        return $arr;
    }

    /**
     * @param      $obj
     * @param bool $asStrictArray
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
     * @throws \ITMH\Soap\Exception\MissingClassMappingException
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     * @throws \ReflectionException
     * @return mixed
     */
    protected function mapObject(
        $obj,
        $className,
        $classMap = array(),
        $classNamespace = ''
    ) {
        if (is_object($obj)) {

            // Check if there is a mapping.
            if (isset($classMap[$className])) {
                $mappedClassName = $classMap[$className];
            } else {
                if ($classNamespace) {
                    $mappedClassName = str_replace('\\\\', '\\',
                        $classNamespace . '\\' . $className);
                } else {
                    throw new MissingClassMappingException('Missing mapping for element "' . $className . '"');
                }
            }

            // Check if local class exists.
            if (!class_exists($mappedClassName)) {
                throw new InvalidClassMappingException('Class not found: "' . $mappedClassName . '"');
            }
            // Get class properties and methods.
            $objProperties = array_keys(get_class_vars($mappedClassName));
            $objMethods = get_class_methods($mappedClassName);

            // Instantiate new mapped object.
            $objInstance = new $mappedClassName();

            // Map remote object to local object.
            $arrObj = get_object_vars($obj);
            foreach ($arrObj as $key => $val) {
                if ($val !== null) {
                    $useSetter = false;
                    if (in_array('set' . $key, $objMethods, true)) {
                        $useSetter = true;
                    } elseif (!in_array($key, $objProperties, true)) {
                        if ($this->strictMapping === true) {
                            throw new InvalidClassMappingException('Property "' . $mappedClassName . '::' . $key . '" doesn\'t exist');
                        }
                    }

                    // If it's not scalar, recursive call the mapping function
                    if (is_array($val) || is_object($val)) {
                        $val = $this->mapObject($val, $key, $classMap,
                            $classNamespace);
                    }

                    // If there is a setter, use it. If not, set the property directly.
                    if ($useSetter) {
                        $setterName = 'set' . $key;

                        // Check if parameter is \DateTime
                        $reflection = new \ReflectionMethod($mappedClassName,
                            $setterName);
                        $params = $reflection->getParameters();
                        if (count($params) !== 1) {
                            throw new InvalidClassMappingException('Wrong Argument Count in Setter for property ' . $key);
                        }
                        $param = reset($params);
                        /* @var $param \ReflectionParameter */

                        // Get the parameter class (if type-hinted)
                        try {
                            $paramClass = $param->getClass();
                        } catch (\ReflectionException $e) {
                            throw new \ReflectionException('Invalid type hint for method "' . $setterName . '"');
                        }
                        if ($paramClass) {
                            $paramClassName = $paramClass->getNamespaceName() . '\\' . $param->getClass()->getName();
                            // If setter parameter is typehinted, cast the value before calling the method
                            if ($paramClassName === '\DateTime') {
                                $val = new \DateTime($val);
                            }
                        }

                        $objInstance->$setterName($val);
                    } else {
                        $objInstance->$key = $val;
                    }
                }
            }

            return $objInstance;
        } elseif (is_array($obj)) {
            // Value is array.
            $returnArray = array();
            // If array mapping exists, map array elements.
            if (array_key_exists('array|' . $className, $classMap)) {
                $className = 'array|' . $className;
                foreach ($obj as $key => $val) {
                    $returnArray[$key] = $this->mapObject($val, $className,
                        $classMap, $classNamespace);
                }
            } else {
                // If array mapping doesn't exist, return the array.
                $returnArray = $obj;
            }

            return $returnArray;
        } else {
            // Value is scalar. Just return it.
            return $obj;
        }
    }

    /**
     * Parse cURL response into header and body
     *
     * Inspired by shuber cURL wrapper.
     *
     * @param string $response
     *
     * @return array
     */
    protected function parseCurlResponse($response)
    {
        $pattern = '|HTTP/\d\.\d.*?$.*?\r\n\r\n|ims';
        preg_match_all($pattern, $response, $matches);
        $header = array_pop($matches[0]);
        # Remove headers from the response body
        $body = str_replace($header, '', $response);

        return [
            'header' => $header,
            'body' => $body
        ];
    }

    /**
     * Log cURL Debug Message
     *
     * @param string    $message
     * @param \DateTime $messageTimestamp
     *
     * @throws \RuntimeException
     */
    protected function logCurlMessage($message, \DateTime $messageTimestamp)
    {
        error_log('[' . $messageTimestamp->format('Y-m-d H:i:s') . ']' . $message . "\n\n");
    }
}
