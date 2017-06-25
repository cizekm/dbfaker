<?php

namespace DbFaker\FakeDataSource;

use Faker\Factory;

class FakeDataSourceFactory
{
	public static function createFakeDataSource(string $locale): FakeDataSource
	{
		$generator = Factory::create($locale);

		return new FakeDataSource($generator);
	}
}
