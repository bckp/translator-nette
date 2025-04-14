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

use Bckp\Translator\Translator;
use Nette\Localization;

use function array_walk;

class NetteTranslator implements Localization\Translator
{
	private Translator $translator;

	/** @api */
	public function __construct(
		private readonly TranslatorProvider $translatorProvider,
		private readonly LocaleProvider $localeProvider,
	) {}

	/**
	 * @api
	 */
	public function getLocale(): string
	{
		return $this->localeProvider->resolve();
	}

	/**
	 * @api
	 */
	#[\Override]
	public function translate(\Stringable|string $message, mixed ...$parameters): string
	{
		// Normalize nette to bckp/translator parameters
		array_walk($parameters, static fn(&$value): string|float|int => $value = is_int($value) || is_float($value) ? $value : (string) $value);
		return $this->getTranslator()->translate($message, ...$parameters);
	}

	private function getTranslator(): Translator
	{
		/** @psalm-suppress RedundantPropertyInitializationCheck */
		return $this->translator ??= $this->translatorProvider->getTranslator($this->getLocale());
	}
}
