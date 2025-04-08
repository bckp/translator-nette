<?php declare(strict_types=1);

namespace Bckp\Translator\Nette\Resolvers;

use Nette\Http\Request;
use Nette\Http\Response;

class CookieResolver
{
	public function __construct(
		protected readonly string $parameterName,
		protected readonly Request $httpRequest,
		protected readonly Response $httpResponse,
	) {}

	/**
	 * @param string[] $allowed
	 */
	public function resolve(array $allowed): ?string
	{
		return $this->httpRequest->getCookie($this->parameterName);
	}

	public function set(string $lang): bool
	{
		try {
			$this->httpResponse->setCookie(
				$this->parameterName,
				$lang,
				null,
			);
			return true;
		} catch (\Throwable) {
			return false;
		}
	}
}
