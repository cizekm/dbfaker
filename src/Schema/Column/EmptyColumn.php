<?php

namespace DbFaker\Schema\Column;

class EmptyColumn extends Column
{
	public function getType(): string
	{
		return self::TYPE_EMPTY;
	}

	public function generateFakeValue($origValue = null)
	{
		return null;
	}
}
