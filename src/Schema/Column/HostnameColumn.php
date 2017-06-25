<?php

namespace DbFaker\Schema\Column;

class HostnameColumn extends Column
{
	public function getType(): string
	{
		return self::TYPE_HOSTNAME;
	}

	public function generateFakeValue($origValue = null)
	{
		$prefix = $this->getRandomIp() ?? $this->getRandomSubdomain();
		$domain = $this->fakeDataSource->getPropnameData(
			'domainName',
			$origValue,
			$this->isUnique(),
			$this->isOptional(),
			$this->getProbability(),
			$this->isDeterministic()
		);

		return trim($domain) == '' ? null : $prefix.'.'.$domain;
	}

	protected function getRandomIp(): ?string
	{
		return $this->fakeDataSource->getPropnameData('ipv4', null, false, true, 0.5, false);
	}

	protected function getRandomSubdomain(): string
	{
		return $this->fakeDataSource->getPropnameData('domainWord', null, false, false, null, false);
	}
}
