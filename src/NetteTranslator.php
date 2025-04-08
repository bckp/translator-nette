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
use Bckp\Translator\TranslatorProvider;
use Nette\Localization;

class NetteTranslator implements Localization\Translator
{
	private Translator $translator;

	public function __construct(
		private readonly TranslatorProvider $translatorProvider,
		private readonly LocaleProvider $localeProvider,
	) {
	}

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
	public function translate(\Stringable|string $message, ...$parameters): string|\Stringable
	{
		return $this->getTranslator()->translate($message, ...$parameters);
	}

	private function getTranslator(): Translator {
		return $this->translator ??= $this->translatorProvider->getTranslator($this->getLocale());
	}
}
