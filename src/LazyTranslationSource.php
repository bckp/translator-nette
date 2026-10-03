<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette;

use Bckp\Translator\Interfaces\TranslationSource;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\Nette\Interfaces\TranslationSourceFactory;

final class LazyTranslationSource implements TranslationSource
{
	private ?TranslationSource $source = null;

	public function __construct(private readonly TranslationSourceFactory $factory)
	{
	}

	#[\Override]
	public function getVersion(string $locale): string
	{
		return $this->source()->getVersion($locale);
	}

	#[\Override]
	public function __invoke(string $locale): MessageCatalogue
	{
		return ($this->source())($locale);
	}

	private function source(): TranslationSource
	{
		return $this->source ??= $this->factory->create();
	}
}
