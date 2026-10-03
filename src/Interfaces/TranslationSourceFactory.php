<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Interfaces;

use Bckp\Translator\Interfaces\TranslationSource;

interface TranslationSourceFactory
{
	public function create(): TranslationSource;
}
