<?php

namespace DbFaker\Schema\Table;

use DbFaker\Config;
use DbFaker\Exception\InvalidArgumentException;
use DbFaker\Exception\InvalidConfigException;
use DbFaker\FakeDataSource\FakeDataSource;
use DbFaker\Schema\Column\Column;
use DbFaker\Schema\Column\ColumnFactory;
use DbFaker\Schema\Key\Key;
use Nette\Neon\Entity;
use Nette\Neon\Neon;

class TableFactory
{
	public static function createTable(string $name, Config $config, FakeDataSource $fakeDataSource): Table
	{
		$table = new Table($name, Key::create($config->get('key')), $config);

		$columnsConfig = $config->get('columns');

		if (!is_array($columnsConfig)) {
			throw new InvalidConfigException(sprintf(
				'Wrong configured columns for table %s',
				$name
			));
		}

		foreach ($columnsConfig as $columnName => $columnConfig) {
			if (is_string($columnConfig) || $columnConfig instanceof Entity) {
				$columnConfig = ['type' => $columnConfig];
			} elseif (!is_array($columnConfig)) {
				throw new InvalidConfigException(sprintf(
					'Invalid column config for table %s, column %s. Required string or array, given %s',
					$table->getName(),
					$columnName,
					gettype($columnConfig)
				));
			}

			if (!isset($columnConfig['type'])) {
				throw new InvalidConfigException(sprintf(
					'Column type not configured for column %s, table %s',
					$columnName,
					$table->getName()
				));
			}

			$columnType = $columnConfig['type'];

			if ($columnType instanceof Entity) {
				if ($columnType->value == Neon::CHAIN) {
					if (count($columnType->attributes) != 2 ||
						!$columnType->attributes[0] instanceof Entity
					) {
						throw new InvalidConfigException(sprintf(
							'Invalid config for column %s in table %s',
							$columnName,
							$table->getName()
						));
					}

					$columnConfig['type'] = Column::TYPE_SIMPLE_FAKER_METHOD;
					$columnConfig['fakerMethod'] = $columnType->attributes[0]->value;
					$columnConfig['fakerMethodArgs'] = $columnType->attributes[0]->attributes;
					$columnConfig['modifiers'] = $columnConfig['modifiers'] ?? [];

					if (!$columnType->attributes[1] instanceof Entity ||
						mb_strpos($columnType->attributes[1]->value, '|') !== 0
					) {
						throw new InvalidConfigException(sprintf(
							'Invalid modifiers config for column %s in table %s',
							$columnName,
							$table->getName()
						));
					}

					$columnConfig['modifiers'] = array_replace(
						$columnConfig['modifiers'],
						array_filter(array_map('trim', explode('|', $columnType->attributes[1]->value)))
					);
				} else {
					$columnConfig['type'] = Column::TYPE_SIMPLE_FAKER_METHOD;
					$columnConfig['fakerMethod'] = $columnType->value;
					$columnConfig['fakerMethodArgs'] = $columnType->attributes;
				}
			}

			$column = ColumnFactory::createColumn($table, $columnName, Config::create($columnConfig), $fakeDataSource);
			$table->addColumn($column);
		}

		return $table;
	}
}
