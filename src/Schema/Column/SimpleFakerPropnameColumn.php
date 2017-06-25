<?php

namespace DbFaker\Schema\Column;

use DbFaker\Config;
use DbFaker\Exception\AnnotationsParseException;
use DbFaker\Exception\InvalidConfigException;
use DbFaker\FakeDataSource\FakeDataSource;
use DbFaker\Schema\Table\Table;
use Faker\Generator;

class SimpleFakerPropnameColumn extends Column
{
	protected $fakerPropname = null;

	protected static $enabledFakerPropnames = null;

	public function __construct(Table $table, $name, Config $config, FakeDataSource $fakeDataSource)
	{
		parent::__construct($table, $name, $config, $fakeDataSource);

		$this->fakerPropname = $this->config->get('fakerPropname');

		if (trim($this->fakerPropname) == '') {
			throw new InvalidConfigException(sprintf('$fakerPropname is not configured for column %s', $this->name));
		}

		if (!$this->isValidFakerPropname($this->fakerPropname)) {
			throw new InvalidConfigException(sprintf(
				'Invalid fakerPropname "%s" for column %s',
				$this->fakerPropname,
				$this->name
			));
		}
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return self::TYPE_SIMPLE_FAKER_PROPNAME;
	}

	public function generateFakeValue($origValue = null)
	{
		return $this->fakeDataSource->getPropnameData(
			$this->fakerPropname,
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
			['fakerPropname' => null]
		);
	}

	/**
	 * @param string $fakerPropname
	 * @return bool
	 */
	protected function isValidFakerPropname(string $fakerPropname): bool
	{
		if (self::$enabledFakerPropnames === null) {
			self::$enabledFakerPropnames = static::findEnabledFakerPropnames();
		}

		if (in_array($fakerPropname, self::$enabledFakerPropnames)) {
			return true;
		}

		if ($this->fakeDataSource) {
			foreach ($this->fakeDataSource->getGeneratorProviders() as $provider) {
				if (is_callable([$provider, $fakerPropname])) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @return array
	 */
	protected static function findEnabledFakerPropnames(): array
	{
		$reflection = new \ReflectionClass(Generator::class);

		if (preg_match_all('/@property\s+.+\s+\$(?<propname>\w+)/i', $reflection->getDocComment(), $matches)) {
			return array_filter($matches['propname']);
		}

		throw new AnnotationsParseException(sprintf(
			'Could not parse annotations of class %s while initializing $enabledFakerPropnames',
			Generator::class
		));
	}
}
