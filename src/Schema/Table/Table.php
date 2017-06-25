<?php

namespace DbFaker\Schema\Table;

use DbFaker\Config;
use DbFaker\Schema\Column\Column;
use DbFaker\Schema\Key\Key;

class Table
{
	/** @var string */
	protected $name = null;

	/** @var Key */
	protected $key = null;

	/** @var Config */
	protected $config = null;

	/** @var Column[] */
	protected $columns = [];

	public function __construct(string $name, Key $key, Config $config)
	{
		$this->name = $name;
		$this->key = $key;
		$this->config = $config;
		$this->columns = [];
	}

	public function addColumn(Column $column): self
	{
		$this->columns[$column->getName()] = $column;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return Key
	 */
	public function getKey(): Key
	{
		return $this->key;
	}

	/**
	 * @return Column[]
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}

	/**
	 * @param bool $includeKey
	 * @return string[]
	 */
	public function getColumnNames($includeKey = false): array
	{
		$columnNames = [];

		if ($includeKey) {
			$columnNames = array_merge($columnNames, $this->key->getColumnNames());
		}

		foreach ($this->columns as $column) {
			$columnNames[] = $column->getName();
		}

		return $columnNames;
	}
}
