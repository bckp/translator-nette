<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette;

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Exceptions\TranslatorException;
use Bckp\Translator\Interfaces\Diagnostics;
use Bckp\Translator\Locale;
use Bckp\Translator\Nette\Interfaces\CatalogueFactory;
use Bckp\Translator\Translator;
use InvalidArgumentException;
use stdClass;

final class TranslatorProvider
{
	private stdClass $factories;

	private stdClass $builders;

	private stdClass $translators;

	public function __construct(private readonly ?Diagnostics $diagnostics = null)
	{
		$this->factories = new stdClass();
		$this->builders = new stdClass();
		$this->translators = new stdClass();
	}

	public function addCatalogueFactory(string $locale, CatalogueFactory $factory): void
	{
		$locale = Locale::normalize($locale);

		if (property_exists($this->factories, $locale)) {
			throw new InvalidArgumentException("Duplicate locale: $locale");
		}
		$this->factories->{$locale} = $factory;
	}

	public function getTranslator(string $locale): Translator
	{
		$locale = Locale::normalize($locale);

		return $this->translators->{$locale} ??= new Translator($this->builder($locale)->compile(), $this->diagnostics);
	}

	public function forceRecompile(string $locale): Translator
	{
		$locale = Locale::normalize($locale);
		$catalogue = $this->builder($locale)->forceRecompile();
		$translator = $this->translators->{$locale} ?? null;

		if ($translator instanceof Translator) {
			$translator->replaceCatalogue($catalogue);
		} else {
			$translator = new Translator($catalogue, $this->diagnostics);
			$this->translators->{$locale} = $translator;
		}

		return $translator;
	}

	private function builder(string $locale): CatalogueBuilder
	{
		$factory = $this->factories->{$locale} ?? null;

		if (!$factory instanceof CatalogueFactory) {
			throw new TranslatorException("Locale '$locale' not found, available locales: "
				. implode(', ', array_keys(get_object_vars($this->factories))));
		}

		return $this->builders->{$locale} ??= $factory->create();
	}
}
