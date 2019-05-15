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

namespace Bckp\Translator\Nette;

use Nette\Localization\ITranslator;

/**
 * Class Translator
 *
 * @package Bckp\Translator\Nette
 */
class Translator implements ITranslator {

	/**
	 * @var \Bckp\Translator\ITranslator
	 */
	private $translator;

	/**
	 * Translator constructor.
	 *
	 * @param \Bckp\Translator\ITranslator $translator
	 */
	public function __construct(\Bckp\Translator\ITranslator $translator) {
		$this->translator = $translator;
	}

	/**
	 * @param $message
	 * @param mixed ...$parameters
	 * @return string
	 */
	function translate($message, ...$parameters): string {
		return $this->translator->translate($message, ...$parameters);
	}
}
