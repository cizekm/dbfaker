<?php

namespace DbFaker\Database\Driver;

use DbFaker\Config;
use DbFaker\Database\Driver\Exception\DriverNotConnectedException;
use DbFaker\Database\Driver\Exception\UpdateException;

class PDOMySQLDriver extends Driver
{
	/** @var \PDO */
	protected $conn = null;

	protected function connect(Config $config): Driver
	{
		$options = [
			\PDO::ATTR_PERSISTENT => true,
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
		];
		$this->conn = new \PDO($this->getPdoDsn($config), $config['username'], $config['password'], $options);

		if ($this->config->get('disableBinlog')) {
			$this->conn->exec('SET SESSION sql_log_bin = 0');
		}

		return $this;
	}

	protected function getPdoDsn(Config $config)
	{
		$dsn = 'mysql:';

		if (trim($host = $config->get('host')) != '') {
			$dsn .= 'host='.$host.';';
		}

		if (trim($port = $config->get('port')) != '') {
			$dsn .= 'port='.$port.';';
		}

		if (trim($database = $config->get('database')) != '') {
			$dsn .= 'dbname='.$database.';';
		}

		if (trim($charset = $config->get('charset')) != '') {
			$dsn .= 'charset='.$charset.';';
		}

		return $dsn;
	}

	/**
	 * @param string $tableName
	 * @param array|null $columns
	 * @return array
	 */
	public function fetchAll(string $tableName, array $columns = null): array
	{
		if (!$this->isConnected()) {
			throw new DriverNotConnectedException('Database driver is not connected');
		}

		if (is_array($columns) && count($columns) == 0) {
			throw new EmptyColumnsListException('Columns list cannot be empty');
		}

		if ($columns === null) {
			$columnsStr = '*';
		} else {
			$columnsStr = implode(',', array_map(function ($columnName) {
				return $this->prepareColumnName($columnName);
			}, $columns));
		};

		$sqlQuery = 'SELECT '.$columnsStr.' FROM '.$this->prepareTableName($tableName);

		$res = $this->conn->query($sqlQuery);

		return $res->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function replace(string $tableName, array $data, array $identifier): bool
	{
		$columns = [];
		$conditions = [];
		$params = [];

		$i = 1;
		foreach ($data as $columnName => $columnValue) {
			$columns[] = $this->prepareColumnName($columnName).' = :param'.$i;
			$params[':param'.$i] = $columnValue;
			$i++;
		}

		foreach ($identifier as $columnName => $columnValue) {
			$conditions[] = $this->prepareColumnName($columnName).' = :param'.$i;
			$params[':param'.$i] = $columnValue;
			$i++;
		}

		$sqlQuery = 'UPDATE '.$this->prepareTableName($tableName).
			' SET '.implode(', ', $columns).
			' WHERE '.implode(' AND ', $conditions);

		$stmt = $this->conn->prepare($sqlQuery);

		try {
			$result = $stmt->execute($params);
		} catch (\PDOException $ex) {
			throw new UpdateException($ex->getMessage(), $ex->getCode());
		}

		return $result;
	}

	public function onTableUpdateStart(string $tableName): Driver
	{
		$this->conn->beginTransaction();

		return $this;
	}

	public function onTableUpdateFinished(string $tableName): Driver
	{
		$this->conn->commit();

		return $this;
	}

	public function onTableUpdateFailed(string $tableName): Driver
	{
		$this->conn->rollBack();

		return $this;
	}

	protected function isConnected(): bool
	{
		return $this->conn !== null;
	}

	protected function prepareColumnName(string $columnName): string
	{
		return '`'.trim($columnName, ' `').'`';
	}

	protected function prepareTableName(string $tableName): string
	{
		return '`'.trim($tableName, ' `').'`';
	}
}
