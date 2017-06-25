<?php

namespace DbFaker\Schema\Key;

use DbFaker\Exception\InvalidArgumentException;

class MultiColumnKey extends Key
{
	/** @var string[] */
	protected $columnNames = null;

	public function __construct(array $columnNames)
	{
		$columnNames = array_filter(array_map('trim', $columnNames));

		if (count($columnNames) == 0) {
			throw new InvalidArgumentException('$columnNames cannot be empty');
		}

		$this->columnNames = $columnNames;
	}

	public function getColumnNames(): array
	{
		return $this->columnNames;
	}
}
