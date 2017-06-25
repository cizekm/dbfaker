<?php

namespace DbFaker\Database\Driver;

use DbFaker\Config;
use DbFaker\Database\Driver\Exception\UpdateException;

abstract class Driver
{
	/** @var Config */
	protected $config = null;

	public function __construct(Config $config)
	{
		$this->config = $config;

		$this->connect($this->config);
	}

	abstract protected function connect(Config $config): self;

	abstract public function fetchAll(string $tableName, array $columns = null): array;

	abstract public function onTableUpdateStart(string $tableName): self;

	abstract public function onTableUpdateFinished(string $tableName): self;

	abstract public function onTableUpdateFailed(string $tableName): self;

	/**
	 * @param string $tableName
	 * @param array $data
	 * @param array $identifier
	 * @return bool
	 * @throws UpdateException
	 */
	abstract public function replace(string $tableName, array $data, array $identifier): bool;

	abstract protected function isConnected(): bool;
}
