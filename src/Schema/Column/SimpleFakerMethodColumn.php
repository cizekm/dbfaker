<?php

namespace DbFaker\Schema\Column;

use DbFaker\Config;
use DbFaker\Exception\AnnotationsParseException;
use DbFaker\Exception\InvalidConfigException;
use DbFaker\FakeDataSource\FakeDataSource;
use DbFaker\Schema\Table\Table;
use Faker\Generator;

class SimpleFakerMethodColumn extends Column
{
	protected $fakerMethod = null;
	protected $fakerMethodArgs = [];

	protected static $enabledFakerMethods = null;

	public function __construct(Table $table, $name, Config $config, FakeDataSource $fakeDataSource)
	{
		parent::__construct($table, $name, $config, $fakeDataSource);

		$this->fakerMethod = $this->config->get('fakerMethod');
		$this->fakerMethodArgs = $this->config->get('fakerMethodArgs');

		if (trim($this->fakerMethod) == '') {
			throw new InvalidConfigException(sprintf('$fakerMethod is not configured for column %s', $this->name));
		}

		if (!$this->isValidFakerMethod($this->fakerMethod)) {
			throw new InvalidConfigException(sprintf(
				'Invalid fakerMethod "%s" for column %s',
				$this->fakerMethod,
				$this->name
			));
		}
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return self::TYPE_SIMPLE_FAKER_METHOD;
	}

	public function generateFakeValue($origValue = null)
	{
		return $this->fakeDataSource->getMethodData(
			$this->fakerMethod,
			$this->fakerMethodArgs,
			$origValue,
			$this->isUnique(),
			$this->isOptional(),
			$this->getProbability(),
			$this->isDeterministic(),
			$this->getDisabledValues()
		);
	}

	/**
	 * @return array
	 */
	protected function getDefaultConfig(): array
	{
		return array_replace(
			parent::getDefaultConfig(),
			[
				'fakerMethod' => null,
				'fakerMethodArgs' => []
			]
		);
	}

	/**
	 * @param string $fakerMethod
	 * @return bool
	 */
	protected function isValidFakerMethod(string $fakerMethod): bool
	{
		if (self::$enabledFakerMethods === null) {
			self::$enabledFakerMethods = static::findEnabledFakerMethods();
		}

		if (in_array($fakerMethod, self::$enabledFakerMethods)) {
			return true;
		}

		if ($this->fakeDataSource) {
			foreach ($this->fakeDataSource->getGeneratorProviders() as $provider) {
				if (is_callable([$provider, $fakerMethod])) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	protected static function findEnabledFakerMethods(): array
	{
		$reflection = new \ReflectionClass(Generator::class);

		if (preg_match_all('/@method\s+.+\s+(?<method>\w+)\(.+\)/i', $reflection->getDocComment(), $matches)) {
			return array_filter($matches['method']);
		}

		throw new AnnotationsParseException(sprintf(
			'Could not parse annotations of class %s while initializing $enabledFakerMethods',
			Generator::class
		));
	}
}
