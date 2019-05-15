<?php declare(strict_types=1);
/**
 * Nette extension for bckp/translator
 * (c) Radovan Kepák
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 *
 * @author Radovan Kepak <radovan@kepak.eu>
 *  --------------------------------------------------------------------------
 */

namespace Bckp\Translator\Nette\Diagnostics;

use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\IDiagnostics;
use Tracy\Debugger;
use Tracy\IBarPanel;

/**
 * Class Panel
 *
 * @package Bckp\Translator\Nette\Diagnostics
 */
class Panel extends Diagnostics implements IDiagnostics, IBarPanel {

	/**
	 * Panel constructor.
	 */
	public function __construct() {
		if (class_exists(Debugger::class))
			Debugger::getBar()->addPanel($this, 'bckp.localization');
	}

	/**
	 * Renders HTML code for custom tab.
	 *
	 * @return string
	 */
	public function getTab(): string {
		$errors = count($this->getWarnings());
		$untranslated = count($this->getUntranslated());

		return '<span title="Translation ' . $this->getLocale() . '"><svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill="#' . ($errors || $untranslated ? 'B71C1C' : '009688') . '" d="M12.87,15.07L10.33,12.56L10.36,12.53C12.1,10.59 13.34,8.36 14.07,6H17V4H10V2H8V4H1V6H12.17C11.5,7.92 10.44,9.75 9,11.35C8.07,10.32 7.3,9.19 6.69,8H4.69C5.42,9.63 6.42,11.17 7.67,12.56L2.58,17.58L4,19L9,14L12.11,17.11L12.87,15.07M18.5,10H16.5L12,22H14L15.12,19H19.87L21,22H23L18.5,10M15.88,17L17.5,12.67L19.12,17H15.88Z" /></svg><span class="tracy-label">' . strtoupper($this->getLocale()) . '</span></span>';
	}

	/**
	 * Renders HTML code for custom panel.
	 *
	 * @return string
	 */
	public function getPanel(): string {
		$return = $e = $u = '';
		$h = 'htmlSpecialChars';

		foreach ($unique = $this->getWarnings() as $message) {
			$e .= '<tr><td>' . $h($message) . '</td></tr>';
		}

		foreach ($untranslated = $this->getUntranslated() as $message) {
			$u .= '<tr><td>' . $h($message) . '</td></tr>';
		}

		if ($e || $u) $return = '<h1>' . strtoupper($this->getLocale()) . ' language</h1><div class="nette-inner bckp-translation">';
		if ($e) $return .= '<h2>Errors: ' . count($unique) . '</h2><table class="tracy-sortable">' . $e . '</table>';
		if ($u) $return .= '<h2>Missing: ' . count($untranslated) . '</h2><table class="tracy-sortable">' . $u . '</table>';
		if ($e || $u) $return .= '</div>';
		return $return;
	}

	/**
	 * @param int $errors
	 * @return string
	 */
	protected function getErrors(int $errors = 0): string {
		if (!$errors)
			return '';
		if ($errors === 1)
			return '1 error';
		return $errors . ' errors';
	}
}
