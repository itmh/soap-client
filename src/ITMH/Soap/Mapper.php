<?php

namespace ITMH\Soap;


use ITMH\Soap\Exception\InvalidClassMappingException;
use ITMH\Soap\Exception\InvalidParameterException;
use ITMH\Soap\Exception\MissingItemException;
use ITMH\Soap\Exception\UnwrappingException;

class Mapper
{
    /**
     * Конфигурация маппинга методов
     *
     * @var array
     */
    protected $config;

    /**
     * Сеттер для конфигурации
     *
     * @param array $config
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * Разворачивает запрос если указан корневой элемент и осуществляет маппинг, если указан класс
     *
     * @param $method
     * @param $data
     *
     * @return array|mixed
     * @throws \ITMH\Soap\Exception\InvalidParameterException
     * @throws \ITMH\Soap\Exception\MissingItemException
     */
    public function mapMethodResponse($method, $data)
    {
        $data = $this->unwrapItem($data);
        if (!$this->isMapMethod($method)) {
            return $data;
        }

        if ($this->isEmptyObject($data)) {
            return null;
        }

        $class = $this->getTargetClass($method);
        if (empty($class)) {
            return $data;
        }

        return $this->mapData($data, $class, $method);
    }

    /**
     * Производит маппинг данных в объект
     *
     * @param mixed  $data     Данные для маппинга
     * @param string $class    Полное имя класса для маппинга
     * @param string $method   Имя ноды в которой находятся данные для маппинга
     *
     * @return array|mixed
     * @throws \ITMH\Soap\Exception\MissingItemException
     */
    public function mapData($data, $class, $method)
    {
        if (is_array($data)) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $this->mapObject($item, $class, $method);
            }
        }

        if (is_object($data)) {
            $result = $this->mapObject($data, $class, $method);
        }

        return $result;
    }


    /**
     * Производит маппинг атрибутов объекта учитывая карту атрибута,
     * если класс имплементирует интерфейс MappingInterface
     *
     * @param $object
     * @param $class
     * @param $method
     *
     * @return mixed
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     * @throws \ITMH\Soap\Exception\MissingItemException
     */
    protected function mapObject($object, $class, $method)
    {
        $objectProperties = get_object_vars($object);
        $mappedObjectMethods = get_class_methods($class);

        $source = $this->getSource($method);

        if (!empty($source) && isset($object->$source)) {
            return $this->mapData($object->$source, $class, $method);
        }

        $map = [];
        $this->checkClassExistence($class);
        $mappedObject = new $class();
        if ($mappedObject instanceof MappableInterface) {
            $map = $mappedObject->getMap();
        }

        foreach ($objectProperties as $key => $value) {
            if (array_key_exists($key, $map)) {
                $key = $map[$key];
            }

            $setterName = $this->getSetterName($key);
            $useSetter = $this->hasMethod($setterName, $mappedObjectMethods);

            if ($useSetter) {
                $mappedObject->$setterName($value);
            } else {
                $mappedObject->$key = $value;
            }

        }

        return $mappedObject;
    }

    /**
     * Получить данные корневого элемента Soap ответа
     *
     * @param string $itemName Имя элемента
     * @param mixed  $data     Soap ответ
     *
     * @return mixed
     * @throws \ITMH\Soap\Exception\InvalidParameterException
     * @throws \ITMH\Soap\Exception\MissingItemException
     * @throws \ITMH\Soap\Exception\UnwrappingException
     */
    public function unwrapItem($data, $itemName = null)
    {
        if (!is_object($data)) {
            throw new InvalidParameterException('Response is not object');
        }

        if (empty($itemName)) {
            $keys = array_keys(get_object_vars($data));
            if (count($keys) === 1) {
                $rootClassName = reset($keys);
                return $data->$rootClassName;
            }

            throw new UnwrappingException('Object has more than one key');
        }

        if (!isset($data->$itemName)) {
            throw new MissingItemException('Item mismatch');
        }

        return $data->$itemName;
    }

    /**
     * Метод для получения ключа конфигурации asArray для данного метода
     *
     * @param string $method Имя метода
     *
     * @return string
     */
    protected function getAsArray($method)
    {
        return $this->getMethodConfigKey($method, 'asArray');
    }

    /**
     * Метод для получения ключа конфигурации source для данного метода
     *
     * @param string $method Имя метода
     *
     * @return string
     */
    protected function getSource($method)
    {
        return $this->getMethodConfigKey($method, 'source');
    }

    /**
     * Метод для получения ключа конфигурации Target для данного метода
     *
     * @param string $method Имя метода
     *
     * @return string
     */
    protected function getTargetClass($method)
    {
        return $this->getMethodConfigKey($method, 'target');
    }

    /**
     * Метод проверяет существование ключа конфигурации для даного метода и возвращает его значение
     *
     * @param string $method Имя метода
     * @param string $key    Ключ конфигурации
     *
     * @return string|null
     */
    protected function getMethodConfigKey($method, $key)
    {
        if ($this->isMapMethod($method) && array_key_exists($key, $this->config[$method])) {
            return $this->config[$method][$key];
        }

        return null;
    }

    /**
     * Проверяет нужно ли мапить данный метод
     *
     * @param string $method Имя метода
     *
     * @return bool
     */
    protected function isMapMethod($method)
    {
        return array_key_exists($method, $this->config);
    }

    /**
     * Проверяет существование метода в списке методов
     *
     * @param string $method        Имя метода
     * @param array  $objectMethods Список методов объекта
     *
     * @return bool
     */
    protected function hasMethod($method, $objectMethods)
    {
        return in_array($method, $objectMethods, true);
    }

    /**
     * Получает имя сеттера
     *
     * @param string $attribute Имя атрибута
     *
     * @return string
     *
     * @codeCoverageIgnore Не содержит логики
     */
    protected function getSetterName($attribute)
    {
        return 'set' . ucfirst($attribute);
    }

    /**
     * Проверяет существование класса
     *
     * @param string $className Имя класса
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
     * Проверяет есть ли у объекта какие-либо публичные свойства
     *
     * @param $object
     *
     * @return bool
     */
    protected function isEmptyObject($object) {
        if (is_object($object)) {
            $vars = get_object_vars($object);
            if(count($vars) === 0) {
                return true;
            }
        }

        return false;
    }
}
