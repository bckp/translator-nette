<?php

namespace Bckp\Translator\Nette\Resolvers;

use Nette\Http\Request;

use function reset;

final class HeaderResolver
{
	public function __construct(
		private readonly Request $httpRequest
	) {}

	public function resolve(array $allowed): ?string
	{
		return $this->httpRequest->detectLanguage($allowed);
	}
}
