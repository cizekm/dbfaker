<?php

namespace DbFaker\Schema\Column;

class MD5PasswordColumn extends PasswordColumn
{
	public function getType(): string
	{
		return self::TYPE_MD5_PASSWORD;
	}

	protected function encryptPassword(string $password): string
	{
		return md5($password);
	}
}
