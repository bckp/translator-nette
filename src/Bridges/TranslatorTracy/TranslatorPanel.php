<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Diagnostics;

use Bckp\Translator\CatalogueStatus;
use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\SourceVersion;
use Bckp\Translator\StringList;
use Tracy\Helpers;
use Tracy\IBarPanel;

final class TranslatorPanel extends Diagnostics implements IBarPanel
{
	private ?StringList $languages = null;

	public function setLanguages(StringList $languages): void
	{
		$this->languages = clone $languages;
	}

	#[\Override]
	public function getTab(): string
	{
		$color = $this->getWarnings()->isEmpty() && $this->getUntranslated()->isEmpty() ? '#009688' : '#B71C1C';

		return '<span title="Translator" style="color:' . $color . '">Translator '
			. Helpers::escapeHtml(strtoupper($this->getLocale())) . '</span>';
	}

	#[\Override]
	public function getPanel(): string
	{
		$html = '<h1>Translator</h1><div class="tracy-inner"><p>Locale: '
			. Helpers::escapeHtml($this->getLocale()) . '; allowed: '
			. Helpers::escapeHtml($this->languages?->join(', ') ?? '') . '</p>';
		$html .= '<h2>Catalogues</h2><table><tr><th>Locale</th><th>Checked</th><th>Recompiled</th><th>Versions</th></tr>';
		$this->eachCatalogue(static function (CatalogueStatus $status) use (&$html): void {
			$html .= '<tr><td>' . Helpers::escapeHtml($status->catalogue->locale) . '</td><td>'
				. ($status->checked ? 'yes' : 'no') . '</td><td>' . ($status->recompiled ? 'yes' : 'no') . '</td><td>';
			$status->catalogue->versions->each(static function (SourceVersion $version) use (&$html): void {
				$html .= Helpers::escapeHtml($version->id) . ': ' . Helpers::escapeHtml($version->version) . '<br>';
			});
			$html .= '</td></tr>';
		});
		$html .= '</table><h2>Warnings</h2><ul>';
		$this->getWarnings()->each(static function (string $warning) use (&$html): void {
			$html .= '<li>' . Helpers::escapeHtml($warning) . '</li>';
		});
		$html .= '</ul><h2>Untranslated</h2><ul>';
		$this->getUntranslated()->each(static function (string $message) use (&$html): void {
			$html .= '<li>' . Helpers::escapeHtml($message) . '</li>';
		});

		return $html . '</ul></div>';
	}
}
