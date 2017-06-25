<?php

namespace DbFaker\Schema\Column;

use DbFaker\Config;
use DbFaker\FakeDataSource\FakeDataSource;
use DbFaker\Schema\Table\Table;

class ColumnFactory
{
	protected const TYPE_CLASS_MAP = [
		Column::TYPE_EMPTY => EmptyColumn::class,
		Column::TYPE_HOSTNAME => HostnameColumn::class,
		Column::TYPE_MD5_PASSWORD => MD5PasswordColumn::class,
		Column::TYPE_SIMPLE_FAKER_METHOD => SimpleFakerMethodColumn::class
	];

	public static function createColumn(
		Table $table,
		string $name,
		Config $config,
		FakeDataSource $fakeDataSource
	): Column {
		$columnType = $config->get('type');

		if (preg_match('/^(?<type>.+?)(?<modifiers>(\|.+?)+)$/', $columnType, $matches)) {
			$columnType = $matches['type'];
			$modifiers = array_filter(array_map('trim', explode('|', $matches['modifiers'])));
		} else {
			$modifiers = [];
		}

		$configArr = $config->toArray();
		$configArr['modifiers'] = array_merge($configArr['modifiers'] ?? [], $modifiers);

		if (isset(self::TYPE_CLASS_MAP[$columnType])) {
			$columnClass = self::TYPE_CLASS_MAP[$columnType];
		} elseif (class_exists($columnType)) {
			$columnClass = $columnType;
		} else {
			$columnClass = SimpleFakerPropnameColumn::class;
			$configArr['fakerPropname'] = $columnType;
		}

		$config = Config::create($configArr);

		return new $columnClass($table, $name, $config, $fakeDataSource);
	}
}
