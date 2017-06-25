<?php

namespace DbFaker;

use DbFaker\Exception\ConfigCreationException;
use DbFaker\Exception\ConfigReadonlyException;
use DbFaker\Exception\FileNotFoundException;
use DbFaker\Exception\FileReadException;
use DbFaker\Exception\InvalidConfigException;
use Nette\Neon\Neon;
use function PHPSTORM_META\type;

class Config implements \ArrayAccess
{
	protected $data = null;

	public function __construct(array $data = null)
	{
		$this->data = $data;
	}

	/**
	 * @param array|Config $data
	 * @return Config
	 */
	public static function create($data): self
	{
		if ($data instanceof self) {
			return $data;
		}

		if (is_array($data)) {
			return static::fromArray($data);
		}

		if (is_string($data) && preg_match('/\.neon$/i', $data)) {
			return static::fromFile($data);
		}

		throw new ConfigCreationException(sprintf(
			'%s requires first argument to be filename, array or instance of %s. %s given',
			__METHOD__,
			static::class,
			gettype($data)
		));
	}

	/**
	 * @param array $data
	 * @return Config
	 */
	public static function fromArray(array $data): self
	{
		return new Config($data);
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return $this->data;
	}

	/**
	 * @param string $filename
	 * @return Config
	 */
	public static function fromFile(string $filename): self
	{
		if (!file_exists($filename) || !is_file($filename)) {
			throw new FileNotFoundException();
		}

		$fileContent = file_get_contents($filename);

		if ($fileContent === false) {
			throw new FileReadException($filename);
		}

		$data = Neon::decode($fileContent);

		return new Config($data);
	}

	/**
	 * @param string $prop
	 * @param bool $asConfigObject
	 * @return mixed
	 */
	public function get(string $prop, bool $asConfigObject = false)
	{
		$propArr = explode('.', $prop);

		$value = $this->data;

		for ($i = 0; $i < count($propArr); $i++) {
			if (!is_array($value) || !array_key_exists($propArr[$i], $value)) {
				throw new InvalidConfigException(sprintf('"%s" not found in config!', $prop));
			}
			$value = $value[$propArr[$i]];
		}

		if ($asConfigObject) {
			if (!is_array($value)) {
				throw new InvalidConfigException(sprintf('Could not make config object from %s', gettype($value)));
			}

			return static::create($value);
		}

		return $value;
	}

	/**
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 * @since 5.0.0
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->data);
	}

	/**
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 * @since 5.0.0
	 */
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	/**
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetSet($offset, $value)
	{
		throw new ConfigReadonlyException(sprintf(
			'Config is read-only. Write value "%s" to config directive "%s" is not possible',
			$value,
			$offset
		));
	}

	/**
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @return void
	 * @since 5.0.0
	 */
	public function offsetUnset($offset)
	{
		throw new ConfigReadonlyException(sprintf(
			'Config is read-only. Delete config directive "%s" is not possible',
			$offset
		));
	}
}
