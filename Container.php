<?php

namespace Syringe;

class InMemoryServiceRepository
{
    private $services = [];

    public function get($id)
    {
        if (!isset($this->services[$id])) {
            throw new \Exception('Service not in repository');
        }

        return $this->services[$id];
    }

    public function add($id, $service)
    {
        $this->services[$id] = $service;
    }
}

/**
 * class ConcreteFactory
 */
class ServiceFactory
{
    /**
     * @var array
     */
    protected $serviceList;
    protected $jsonObj;
    const JSON_FILE = 'services.json';

    /**
     * You can imagine to inject your own id list or merge with
     * the default ones...
     */
    public function __construct()
    {
        $this->loadServiceList();
    }

    private function loadJsonFile()
    {
        $string        = file_get_contents(self::JSON_FILE);
        $this->jsonObj = json_decode($string);
    }

    private function loadServiceList()
    {
        $this->loadJsonFile();

        foreach ($this->jsonObj->services as $service) {
            $this->serviceList[$service->id] = $service;
        }
    }

    /**
     * @param array $serviceData
     *
     * @return array
     */
    private function getArgs($serviceData)
    {
        $args = [];
        if (isset($serviceData->arguments)) {
            foreach ($serviceData->arguments as $arg) {
                $args[] = $this->instantiateService($arg->id);
            }
        }

        return $args;
    }

    private function instantiateService($id)
    {
        $serviceData = $this->serviceList[$id];

        $reflector = new \ReflectionClass($serviceData->class);

        return $reflector->newInstanceArgs($this->getArgs($serviceData));
    }

    /**
     * Creates a vehicle
     *
     * @param string $id a known id key
     *
     * @return a registered service
     *
     * @throws \InvalidArgumentException
     */
    public function create($id)
    {

        if (!isset($this->serviceList[$id])) {
            throw new \InvalidArgumentException("'$id' is not a registered service");
        }

        return $this->instantiateService($id);
    }
}

class Container
{
    /**
     * @var Singleton reference to singleton instance
     */
    private static $instance;

    /**
     * @var InMemoryServiceRepository
     */
    private $repository;

    private $factory;

    /**
     * is not allowed to call from outside: private!
     */
    private function __construct()
    {
        $this->repository = new InMemoryServiceRepository();
        $this->factory    = new ServiceFactory();
    }

    /**
     * prevent the instance from being cloned
     *
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized
     *
     * @return void
     */
    private function __wakeup()
    {
    }

    /**
     * Creates and adds a service to the repository
     *
     * @param $id
     *
     * @return a registered service
     */
    private function fetchService($id)
    {
        $service = $this->factory->create($id);
        $this->repository->add($id, $service);

        return $service;
    }

    /**
     * @return self
     */
    public static function getInstance()
    {

        if (null === static::$instance) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Gets the instance via lazy initialization (created on first usage)
     *
     * @param $id
     *
     * @return a registered service
     */
    public function get($id)
    {
        try {
            return $this->repository->get($id);
        } catch (\Exception $e) {
            return $this->fetchService($id);
        }
    }
}
