<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Interfaces;

interface Translator extends \Nette\Localization\Translator
{
	public function getLocale(): string;

	public function forceRecompile(?string $locale = null): void;
}
