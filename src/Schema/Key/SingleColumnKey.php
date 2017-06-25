<?php

namespace DbFaker\Schema\Key;

use DbFaker\Exception\InvalidArgumentException;

class SingleColumnKey extends Key
{
	/** @var string */
	protected $columnName = null;

	public function __construct(string $columnName)
	{
		if (trim($columnName) == '') {
			throw new InvalidArgumentException('$columnName cannot be empty!');
		}

		$this->columnName = $columnName;
	}

	public function getColumnNames(): array
	{
		return [$this->columnName];
	}
}
