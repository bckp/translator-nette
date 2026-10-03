<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Resolvers;

use Bckp\Translator\StringList;
use Nette\Http\Request;

final readonly class HeaderLocaleResolver implements LocaleResolver
{
	public function __construct(private Request $httpRequest)
	{
	}

	#[\Override]
	public function resolve(StringList $allowed): ?string
	{
		$languages = [];
		$allowed->each(static function (string $locale) use (&$languages): void {
			$languages[] = $locale;
		});

		return $this->httpRequest->detectLanguage($languages);
	}
}
