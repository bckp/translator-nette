<?php

declare(strict_types=1);

/**
 * Nette extension for bckp/translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.dev>
 *  --------------------------------------------------------------------------
 */

namespace Bckp\Translator\Nette;

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Exceptions\TranslatorException;
use Bckp\Translator\Interfaces\Diagnostics;
use Bckp\Translator\Translator;

use function array_key_exists;

final class TranslatorProvider
{
	/**
	 * @var array<string, CatalogueBuilder>
	 */
	private array $builder = [];

	/**
	 * @var array<string, Translator>
	 */
	private array $translator = [];

	public function __construct(
		private readonly Diagnostics $diagnostics,
	) {}

	/**
	 * @api
	 */
	public function addCatalogueBuilder(CatalogueBuilder $builder): void
	{
		$this->builder[$builder->getLocale()] = $builder;
	}

	/**
	 * @api
	 * @throws TranslatorException
	 */
	public function getCatalogue(string $locale): Translator
	{
		$locale = $this->normalizeLocale($locale);

		if (!array_key_exists($locale, $this->builder)) {
			throw new TranslatorException("Locale {$locale} not found, available locales: " . implode(', ', array_keys($this->builder)));
		}

		return $this->translator[$locale] ??= new Translator($this->builder[$locale]->compile(), $this->diagnostics);
	}

	private function normalizeLocale(string $locale): string
	{
		return strtolower($locale);
	}
}
