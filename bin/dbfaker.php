<?php
error_reporting(E_ALL | E_STRICT);

require_once __DIR__.'/../vendor/autoload.php';

$configFilename = __DIR__.'/../config/config.neon';

$config = \DbFaker\Config::fromFile($configFilename);

$faker = new \DbFaker\DbFaker($config);

$faker->process();

return 0;
