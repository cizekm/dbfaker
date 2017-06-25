<?php

namespace DbFaker\Database\Driver;

use DbFaker\Config;
use DbFaker\Database\Driver\Exception\DriverCreateException;

class DriverFactory
{
	public static function createDriver(string $driverType, Config $config): Driver
	{
		$driver = null;

		switch ($driverType) {
			case 'pdo_mysql':
				$driver = new PDOMySQLDriver($config);
				break;
			default:
				throw new DriverCreateException(sprintf('Unknown database driver driver "%s"', $driverType));
		}

		return $driver;
	}
}
