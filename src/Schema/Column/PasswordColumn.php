<?php

namespace DbFaker\Schema\Column;

use DbFaker\Config;
use DbFaker\FakeDataSource\FakeDataSource;
use DbFaker\Schema\Table\Table;

abstract class PasswordColumn extends Column
{
	const PASSWORD_RANDOM = 'random';

	protected $password = self::PASSWORD_RANDOM;

	public function __construct(Table $table, $name, Config $config, FakeDataSource $fakeDataSource)
	{
		parent::__construct($table, $name, $config, $fakeDataSource);

		$this->password = $this->config->get('password');
	}

	public function generateFakeValue($origValue = null)
	{
		return $this->encryptPassword($this->getPasswordPlaintext($origValue));
	}

	protected function getDefaultConfig(): array
	{
		return array_replace(
			parent::getDefaultConfig(),
			[
				'password' => self::PASSWORD_RANDOM,
				'preserveEmpty' => false,
				'deterministic' => false
			]
		);
	}

	protected function getPasswordPlaintext($origValue = null)
	{
		return $this->password == self::PASSWORD_RANDOM ?
			$this->fakeDataSource->getPropnameData(
				'password',
				$origValue,
				$this->isUnique(),
				$this->isOptional(),
				$this->getProbability(),
				$this->isDeterministic()
			) : $this->password;
	}

	/**
	 * @param string $password
	 * @return string
	 */
	abstract protected function encryptPassword(string $password): string;
}
