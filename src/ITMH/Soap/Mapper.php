<?php

namespace ITMH\Soap;


use ITMH\Soap\Exception\InvalidClassMappingException;
use ITMH\Soap\Exception\InvalidParameterException;
use ITMH\Soap\Exception\MissingItemException;
use ITMH\Soap\Exception\MissingRootException;
use ITMH\Soap\MappingInterface;

class Mapper
{
    /**
     * Конфигурация маппинга методов
     *
     * @var array
     */
    protected $config = [];

    /**
     * Сеттер для конфигурации
     *
     * @param array $config Конфигурация маппинга
     *
     * @return void
     *
     * @codeCoverageIgnore Не содержит логики
     */
    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * Разворачивает запрос если указан корневой элемент и осуществляет маппинг, если указан класс
     *
     * @param string $method Имя метода
     * @param mixed  $data   Soap ответ
     *
     * @return array|mixed
     * @throws \ITMH\Soap\Exception\InvalidParameterException
     * @throws \ITMH\Soap\Exception\MissingItemException
     */
    public function mapMethodResponse($method, $data)
    {
        if (!$this->isMapMethod($method)) {
            return $data;
        }

        $root = $this->getRootName($method);
        if (!empty($root)) {
            $data = $this->unwrapItem($root, $data);
        }

        $class = $this->getMappingClass($method);
        if (empty($class)) {
            return $data;
        }

        return $this->mapData($data, $class, $this->getItemName($method));
    }

    /**
     * Производит маппинг данных в объект
     *
     * @param mixed  $data     Данные для маппинга
     * @param string $class    Полное имя класса для маппинга
     * @param null   $itemName Имя ноды в которой находятся данные для маппинга
     *
     * @return array|mixed
     * @throws \ITMH\Soap\Exception\MissingItemException
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     * @throws \ITMH\Soap\Exception\InvalidParameterException
     */
    public function mapData($data, $class, $itemName = null)
    {
        if (!$this->isComplex($data)) {
            return $data;
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $item) {
                $result[] = $this->mapObject($this->unwrapItem($itemName, $item), $class);
            }
        }

        if (is_object($data)) {
            $result = $this->mapObject($this->unwrapItem($itemName, $data), $class);
        }

        return $result;
    }

    /**
     * Проверяет является ли переменная массивом или объектом
     *
     * @param mixed $data Данные
     *
     * @return bool
     */
    public function isComplex($data)
    {
        return is_array($data) || is_object($data);
    }


    /**
     * Производит маппинг атрибутов объекта учитывая карту атрибута,
     * если класс имплементирует интерфейс MappingInterface
     *
     * @param mixed  $object Объект
     * @param string $class  Имя класса в который будет производиться маппинг
     *
     * @return mixed
     * @throws \ITMH\Soap\Exception\InvalidClassMappingException
     */
    protected function mapObject($object, $class)
    {
        $objectProperties = get_object_vars($object);

        $map = [];
        $this->checkClassExistence($class);
        $mappedObject = new $class();
        if ($mappedObject instanceof MappingInterface) {
            $map = $mappedObject->getMap();
        }

        foreach ($objectProperties as $key => $value) {
            if (array_key_exists($key, $map)) {
                $key = $map[$key];
            }

            $mappedObject->$key = $value;
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
     */
    public function unwrapItem($itemName, $data)
    {
        if (!is_object($data)) {
            throw new InvalidParameterException('Response is not object');
        }

        if (!isset($data->$itemName)) {
            throw new MissingItemException('Item mismatch');
        }

        return $data->$itemName;
    }

    /**
     * Метод для получения ключа конфигурации root для данного метода
     *
     * @param string $method Имя метода
     *
     * @return string
     *
     * @codeCoverageIgnore Не содержит логики
     */
    protected function getRootName($method)
    {
        return $this->getMethodConfigKey($method, 'root');
    }

    /**
     * Метод для получения ключа конфигурации item для данного метода
     *
     * @param string $method Имя метода
     *
     * @return string
     *
     * @codeCoverageIgnore Не содержит логики
     */
    protected function getItemName($method)
    {
        return $this->getMethodConfigKey($method, 'item');
    }

    /**
     * Метод для получения ключа конфигурации class для данного метода
     *
     * @param string $method Имя метода
     *
     * @return string
     *
     * @codeCoverageIgnore Не содержит логики
     */
    protected function getMappingClass($method)
    {
        return $this->getMethodConfigKey($method, 'class');
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
}
