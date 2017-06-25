<?php

namespace DbFaker\FakeDataSource;

use Faker\Factory;
use Faker\Generator;
use Faker\Provider\Base;

class FakeDataSource
{
	const DEFAULT_OPTIONAL_PROBABILITY = 0.5;
	const MAX_RETRIES_CNT = 10000;

	/** @var Generator */
	protected $generator = null;

	protected $cachedData = [];

	public function __construct(Generator $generator = null)
	{
		$this->generator = $generator ? : Factory::create();
	}

	/**
	 * @return Base[]
	 */
	public function getGeneratorProviders(): array
	{
		return $this->generator ? $this->generator->getProviders() : [];
	}

	/**
	 * @param string $propName
	 * @param mixed $origValue
	 * @param bool $unique
	 * @param bool $optional
	 * @param float|null $probability
	 * @param bool $deterministic
	 * @param array $disabledValues
	 * @return mixed
	 */
	public function getPropnameData(
		string $propName,
		$origValue = null,
		bool $unique = false,
		bool $optional = false,
		float $probability = null,
		bool $deterministic = true,
		array $disabledValues = []
	) {
		$cacheKey = 'p_'.$propName;

		$callback = function ($generator) use ($propName) {
			return $generator->$propName;
		};

		return $this->getData(
			$callback,
			$cacheKey,
			$origValue,
			$unique,
			$optional,
			$probability,
			$deterministic,
			$disabledValues
		);
	}

	/**
	 * @param string $methodName
	 * @param array $args
	 * @param mixed $origValue
	 * @param bool $unique
	 * @param bool $optional
	 * @param float|null $probability
	 * @param bool $deterministic
	 * @param array $disabledValues
	 * @return mixed
	 */
	public function getMethodData(
		string $methodName,
		array $args = [],
		$origValue = null,
		bool $unique = false,
		bool $optional = false,
		float $probability = null,
		bool $deterministic = true,
		array $disabledValues = []
	) {
		$cacheKey = 'm_'.$methodName.'_'.serialize($args);

		$callback = function ($generator) use ($methodName, $args) {
			return $generator->$methodName(...$args);
		};

		return $this->getData(
			$callback,
			$cacheKey,
			$origValue,
			$unique,
			$optional,
			$probability,
			$deterministic,
			$disabledValues
		);
	}

	/**
	 * @param callable $callback
	 * @param string $cacheKey
	 * @param null $origValue
	 * @param bool $unique
	 * @param bool $optional
	 * @param float|null $probability
	 * @param bool $deterministic
	 * @param array $disabledValues
	 * @return mixed
	 */
	protected function getData(
		callable $callback,
		string $cacheKey,
		$origValue = null,
		bool $unique = false,
		bool $optional = false,
		float $probability = null,
		bool $deterministic = true,
		array $disabledValues = []
	) {
		$origValue = (string)$origValue;

		if ($deterministic &&
			trim($origValue) != '' &&
			isset($this->cachedData[$cacheKey][$origValue]) &&
			!$this->isDisabledValue($this->cachedData[$cacheKey][$origValue], $disabledValues)
		) {
			return $this->cachedData[$cacheKey][$origValue];
		}

		if ($unique) {
			$generator = $this->generator->unique();
		} elseif ($optional) {
			$generator = $this->generator->optional($probability ?? self::DEFAULT_OPTIONAL_PROBABILITY);
		} else {
			$generator = $this->generator;
		}

		$i = 0;
		do {
			$data = call_user_func($callback, $generator);
			$i++;
		} while ($this->isDisabledValue($data, $disabledValues) && $i < self::MAX_RETRIES_CNT);

		if ($i >= self::MAX_RETRIES_CNT && $this->isDisabledValue($data, $disabledValues)) {
			trigger_error(
				sprintf('Max retries count exceeded. Value "%s" is in $disabledValues.', $data),
				E_USER_WARNING
			);
		}

		if ($deterministic && trim($origValue) != '') {
			$this->cachedData[$cacheKey][$origValue] = $data;
		}

		return $data;
	}

	protected function isDisabledValue($value, array $disabledValues): bool
	{
		if (is_string($value)) {
			$value = trim(mb_strtolower($value));
		}

		return in_array($value, $disabledValues);
	}
}
