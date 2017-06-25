<?php

namespace DbFaker\Schema\Column;

use DbFaker\Config;
use DbFaker\Exception\UnknownColumnModifierException;
use DbFaker\FakeDataSource\FakeDataSource;
use DbFaker\Schema\Table\Table;

/**
 * Abstract class for database column
 */
abstract class Column
{
	const TYPE_EMPTY = 'empty';
	const TYPE_HOSTNAME = 'hostname';
	const TYPE_MD5_PASSWORD = 'md5password';
	const TYPE_SIMPLE_FAKER_PROPNAME = 'simpleFakerPropname';
	const TYPE_SIMPLE_FAKER_METHOD = 'simpleFakerMethod';

	/** @var Table */
	protected $table = null;

	/** @var string */
	protected $name = null;

	/** @var Config */
	protected $config = null;

	/** @var FakeDataSource */
	protected $fakeDataSource = null;

	/** @var string */
	protected $modifiers = [];

	protected $disabledValues = [];

	public function __construct(
		Table $table,
		string $name,
		Config $config,
		FakeDataSource $fakeDataSource
	) {
		$this->table = $table;
		$this->name = $name;
		$this->config = Config::create(array_replace($this->getDefaultConfig(), $config->toArray()));
		$this->fakeDataSource = $fakeDataSource;

		$this->modifiers = $this->config->get('modifiers');
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function isUnique(): bool
	{
		return $this->config->get('unique');
	}

	protected function isPreserveEmpty(): bool
	{
		return $this->config->get('preserveEmpty');
	}

	protected function isOptional(): bool
	{
		return $this->config->get('optional');
	}

	protected function getProbability(): ?float
	{
		return $this->config->get('probability');
	}

	protected function isDeterministic(): bool
	{
		return $this->config->get('deterministic');
	}

	protected function getDisabledValues(): array
	{
		return $this->disabledValues;
	}

	public function addDisabledValue($disabledValue): self
	{
		if (is_string($disabledValue)) {
			$disabledValue = trim(mb_strtolower($disabledValue));
		}

		$this->disabledValues[] = $disabledValue;

		return $this;
	}

	public function getFakeValue($origValue = null)
	{
		if (trim($origValue) == '' && $this->isPreserveEmpty()) {
			return $origValue;
		}

		return $this->applyModifiers($this->generateFakeValue($origValue));
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	protected function applyModifiers($value)
	{
		foreach ($this->modifiers as $modifier) {
			$value = $this->applyModifier($modifier, $value);
		}

		return $value;
	}

	/**
	 * @param string $modifier
	 * @param mixed $value
	 * @return mixed
	 */
	protected function applyModifier(string $modifier, $value)
	{
		switch ($modifier) {
			case 'nospaces':
				$value = preg_replace('/\s+/', '', $value);
				break;
			case 'string':
				$value = (string)$value;
				break;
			default:
				throw new UnknownColumnModifierException(
					sprintf(
						'Unknown column modifier "%s"',
						$modifier
					)
				);
		}

		return $value;
	}

	protected function getDefaultConfig(): array
	{
		return [
			'unique' => false,
			'optional' => false,
			'probability' => null,
			'preserveEmpty' => true,
			'deterministic' => true,
			'modifiers' => []
		];
	}

	abstract public function getType(): string;

	abstract public function generateFakeValue($origValue = null);
}
