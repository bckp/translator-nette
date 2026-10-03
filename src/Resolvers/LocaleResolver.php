<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Resolvers;

use Bckp\Translator\StringList;

interface LocaleResolver
{
	public function resolve(StringList $allowed): ?string;
}
