<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Resolvers;

use Bckp\Translator\Locale;
use Bckp\Translator\StringList;
use InvalidArgumentException;
use Nette\Http\Request;
use Nette\Http\Response;
use Throwable;

final readonly class CookieLocaleResolver implements LocaleResolver
{
	public function __construct(
		private string $parameterName,
		private Request $httpRequest,
		private Response $httpResponse,
	) {
	}

	#[\Override]
	public function resolve(StringList $allowed): ?string
	{
		$value = $this->httpRequest->getCookie($this->parameterName);

		if ($value === null) {
			return null;
		}

		try {
			$locale = Locale::normalize($value);
		} catch (InvalidArgumentException) {
			return null;
		}

		return $allowed->contains($locale) ? $locale : null;
	}

	public function set(string $locale): bool
	{
		try {
			$this->httpResponse->setCookie($this->parameterName, Locale::normalize($locale), null);

			return true;
		} catch (Throwable) {
			return false;
		}
	}
}
