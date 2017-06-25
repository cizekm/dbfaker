<?php

namespace DbFaker\Database;

use DbFaker\Config;
use DbFaker\Database\Driver\Driver;
use DbFaker\Database\Driver\DriverFactory;
use DbFaker\Database\Driver\Exception\UpdateException;

class Connection
{
	/** @var  Driver */
	protected $driver = null;

	protected $configDefaults = [
		'driver' => 'pdo_mysql',
		'host' => 'localhost',
		'port' => null,
		'username' => '',
		'password' => '',
		'database' => null,
		'charset' => 'utf8',
		'ignoreUpdateExceptions' => false
	];

	/** @var Config  */
	protected $configData = [];

	public function __construct(Config $config)
	{
		$this->config = Config::create(array_replace($this->configDefaults, $config->toArray()));

		$this->driver = DriverFactory::createDriver($this->config->get('driver'), $this->config);
	}

	/**
	 * @param string $tableName
	 * @param array $columns
	 * @return array
	 */
	public function fetchAll(string $tableName, array $columns): array
	{
		return $this->driver->fetchAll($tableName, $columns);
	}

	/**
	 * @param string $tableName
	 * @param array $data
	 * @param array $identifier
	 * @return bool
	 */
	public function replace(string $tableName, array $data, array $identifier): bool
	{
		try {
			$result = $this->driver->replace($tableName, $data, $identifier);
		} catch (UpdateException $ex) {
			if ($this->config->get('ignoreUpdateExceptions')) {
				trigger_error($ex->getMessage(), E_USER_WARNING);
				$result = 0;
			} else {
				$this->driver->onTableUpdateFailed($tableName);
				throw $ex;
			}
		}

		return $result;
	}

	public function onTableUpdateStart(string $tableName): self
	{
		$this->driver->onTableUpdateStart($tableName);

		return $this;
	}

	public function onTableUpdateFinished(string $tableName): self
	{
		$this->driver->onTableUpdateFinished($tableName);

		return $this;
	}
}
