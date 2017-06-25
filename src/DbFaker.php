<?php

namespace DbFaker;

use DbFaker\Database\Connection;
use DbFaker\Exception\InvalidColumnTypeException;
use DbFaker\Exception\InvalidDataException;
use DbFaker\Exception\UnknownColumnModifierException;
use DbFaker\FakeDataSource\FakeDataSource;
use DbFaker\FakeDataSource\FakeDataSourceFactory;
use DbFaker\Schema\Column\Column;
use DbFaker\Schema\Key\Key;
use DbFaker\Schema\Table\Table;
use DbFaker\Schema\Table\TableFactory;

class DbFaker
{
	/** @var Config */
	protected $config = null;

	/** @var Connection */
	protected $connection = null;

	/** @var FakeDataSource */
	protected $fakeDataSource = null;

	/** @var Table[] */
	protected $tables = [];

	public function __construct(Config $config)
	{
		$this->config = $config;

		$this->init($config);
	}

	/**
	 * @param Config $config
	 * @return DbFaker
	 */
	protected function init(Config $config): self
	{
		$this->connection = new Connection($config->get('connection', true));

		$this->fakeDataSource = FakeDataSourceFactory::createFakeDataSource($config->get('faker.locale'));

		foreach ($config->get('faker.tables') as $tableName => $tableConfig) {
			$this->tables[$tableName] = TableFactory::createTable(
				$tableName,
				Config::create($tableConfig),
				$this->fakeDataSource
			);
		}

		return $this;
	}

	/**
	 * @return DbFaker
	 */
	public function process(): self
	{
		$tst = microtime(true);

		$tablesCnt = count($this->tables);

		$i = 0;
		foreach ($this->tables as $tableName => $table) {
			$st = microtime(true);
			echo sprintf("Processing table %s... (%d of %d)\n", $tableName, ++$i, $tablesCnt);
			$this->processTable($table);
			echo "table processed in ".number_format(microtime(true) - $st, 6)." sec.\n";
			echo "memory: ".number_format(memory_get_usage(true), 2)."\n";
			echo str_repeat('=', 30)."\n";
		}

		echo "All tables processed in ".number_format(microtime(true) - $tst, 6)." sec.\n";

		return $this;
	}

	/**
	 * @param Table $table
	 * @return DbFaker
	 */
	protected function processTable(Table $table): self
	{
		echo "loading table data...\n";
		$data = $this->loadTableData($table);

		echo "initializing disabled values...\n";
		$this->initDisabledvalues($table, $data);

		echo sprintf("faking table data... (%d rows)\n", count($data));
		$data = $this->fakeTableData($table, $data);

		echo "saving faked table data...\n";
		$this->saveFakedData($table, $data);

		return $this;
	}

	/**
	 * @param Table $table
	 * @return array
	 */
	protected function loadTableData(Table $table): array
	{
		$data = [];

		foreach ($this->connection->fetchAll($table->getName(), $table->getColumnNames(true)) as $dataRow) {
			$data[$this->getDataRowKey($table->getKey(), $dataRow)] = $this->prepareDataRow($table, $dataRow);
		}

		return $data;
	}

	/**
	 * @param Key $key
	 * @param array $dataRow
	 * @return string
	 */
	protected function getDataRowKey(Key $key, array $dataRow): string
	{
		$idArr = [];

		foreach ($key->getColumnNames() as $columnName) {
			if (!isset($dataRow[$columnName])) {
				throw new InvalidDataException(
					sprintf(
						'Missing identifier %s in data row!',
						$columnName
					)
				);
			}

			$idArr[] = $columnName.':'.$dataRow[$columnName];
		}

		return implode('__', $idArr);
	}

	/**
	 * @param Table $table
	 * @param array $dataRow
	 * @return array
	 */
	protected function prepareDataRow(Table $table, array $dataRow): array
	{
		$keyValue = [];

		foreach ($table->getKey()->getColumnNames() as $columnName) {
			if (!isset($dataRow[$columnName])) {
				throw new InvalidDataException(
					sprintf(
						'Missing identifier %s in data row!',
						$columnName
					)
				);
			}

			$keyValue[$columnName] = $dataRow[$columnName];
			unset($dataRow[$columnName]);
		}

		$dataRow[Key::COLUMN_NAME] = $keyValue;

		return $dataRow;
	}

	protected function initDisabledValues(Table $table, array $data): self
	{
		foreach ($table->getColumns() as $column) {
			if ($column->isUnique()) {
				foreach ($data as $dataRow) {
					$column->addDisabledValue($dataRow[$column->getName()]);
				}
			}
		}

		return $this;
	}

	protected function fakeTableData(Table $table, array $data): array
	{
		$rowsCnt = count($data);

		$i = 0;
		foreach ($data as $key => $dataRow) {
			$data[$key] = $this->fakeTableDataRow($table, $dataRow);
			$this->showProgress(++$i, $rowsCnt);
		}

		return $data;
	}

	protected function fakeTableDataRow(Table $table, array $dataRow): array
	{
		foreach ($table->getColumns() as $column) {
			$columnName = $column->getName();

			if (!array_key_exists($columnName, $dataRow)) {
				throw new InvalidDataException(sprintf('Data row does not contain column %s', $columnName));
			}

			$dataRow[$columnName] = $this->getColumnFakeValue($column, $dataRow);
		}

		return $dataRow;
	}

	protected function getColumnFakeValue(Column $column, array $dataRow)
	{
		return $column->getFakeValue($dataRow[$column->getName()]);
	}

	protected function saveFakedData(Table $table, array $data): self
	{
		$this->connection->onTableUpdateStart($table->getName());

		$rowsCnt = count($data);

		$i = 0;
		foreach ($data as $dataRow) {
			$this->saveFakedDataRow($table, $dataRow);
			$this->showProgress(++$i, $rowsCnt);
		}

		$this->connection->onTableUpdateFinished($table->getName());

		return $this;
	}

	/**
	 * @param Table $table
	 * @param array $dataRow
	 * @return bool
	 */
	protected function saveFakedDataRow(Table $table, array $dataRow): bool
	{
		if (!isset($dataRow[Key::COLUMN_NAME])) {
			throw new InvalidDataException('Data row does not contain identifier!');
		}

		$identifier = $dataRow[Key::COLUMN_NAME];
		unset($dataRow[Key::COLUMN_NAME]);

		$columnNames = $table->getColumnNames();

		$dataRow = array_filter(
			$dataRow,
			function ($columnName) use ($columnNames) {
				return in_array($columnName, $columnNames);
			},
			ARRAY_FILTER_USE_KEY
		);

		return $this->connection->replace($table->getName(), $dataRow, $identifier);
	}

	/**
	 * @param int $actualCnt
	 * @param int $totalCnt
	 * @return DbFaker
	 */
	protected function showProgress(int $actualCnt, int $totalCnt): self
	{
		$fivePercent = (int)ceil($totalCnt / 20);
		if ($actualCnt % $fivePercent == 0) {
			echo sprintf(
				"%d%% - memory %s\n",
				(int)($actualCnt / $fivePercent * 5),
				number_format(memory_get_usage(true), 2)
			);
		}

		return $this;
	}
}
