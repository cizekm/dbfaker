<?php

namespace DbFaker\Schema\Key;

use DbFaker\Exception\InvalidArgumentException;

abstract class Key
{
	const COLUMN_NAME = '_dbfaker_key';

	/**
	 * @param string|array $columnName
	 * @return Key
	 */
	public static function create($columnName): Key
	{
		if (is_string($columnName)) {
			return new SingleColumnKey($columnName);
		} elseif (is_array($columnName)) {
			return new MultiColumnKey($columnName);
		} else {
			throw new InvalidArgumentException(sprintf(
				'%s requires first argument to be string or array. %s given',
				__METHOD__,
				gettype($columnName)
			));
		}
	}

	/**
	 * @return string[]
	 */
	abstract public function getColumnNames(): array;
}
