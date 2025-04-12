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

namespace Bckp\Translator\Nette\Diagnostics;

use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\Nette\Resolvers\Resolver;
use Psalm\Issue\UnusedVariable;
use Tracy\IBarPanel;
use Nette\Utils\Helpers;

use function count;
use function strtoupper;

class TranslatorPanel extends Diagnostics implements IBarPanel
{
	/** @var Resolver[] */
	private array $resolvers = [];

	/** @var string[] */
	private array $languages = [];

	/**
	 * @api
	 * @param string[] $languages
	 */
	public function setResolvers(array $languages, Resolver ...$resolvers): void
	{
		$this->languages = $languages;
		$this->resolvers = $resolvers;
	}

	#[\Override]
	public function getTab(): string
	{
		/** @psalm-suppress UnusedVariable */
		return Helpers::capture(function () {
			$color = !empty($this->getWarnings()) || !empty($this->getUntranslated()) ? 'B71C1C' : '009688';
			$locale = strtoupper($this->getLocale());

			require __DIR__ . '/dist/tab.phtml';
		});
	}

	#[\Override]
	public function getPanel(): string
	{
		/** @psalm-suppress UnusedVariable */
		return Helpers::capture(function () {
			$locale = strtoupper($this->getLocale());

			$errors = $this->getWarnings();
			$countErrors = count($errors);
			$hasErrors = $countErrors > 0;

			$untranslated = $this->getUntranslated();
			$countUntranslated = count($untranslated);
			$hasUntranslated = $countUntranslated > 0;

			$resolvers = $this->resolvers;
			$languages = $this->languages;

			require __DIR__ . '/dist/panel.phtml';
		});
	}
}
