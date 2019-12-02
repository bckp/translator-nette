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
 * Class TranslatorCompatibility
 *
 * @package Bckp\Translator\Nette\Compatibility
 */
final class CompatibilityTranslator implements ITranslator {

	/**
	 * @var \Bckp\Translator\ITranslator
	 */
	private $translator;

	/**
	 * TranslatorCompatibility constructor.
	 *
	 * @param \Bckp\Translator\ITranslator $translator
	 */
	public function __construct(\Bckp\Translator\ITranslator $translator) {
		$this->translator = $translator;
	}

	/**
	 * @param array|string $message
	 * @param mixed ...$parameters
	 * @return string
	 */
	public function translate($message, ...$parameters): string {
		if (empty($parameters))
			return $this->translator->translate($message);

		if (is_array($parameters[0]))
			return $this->translator->translate($message, ...$parameters[0]);

		if (is_numeric($parameters[0])) {
			$count = $parameters[0];
			unset($parameters[0]);
			$arguments = (!empty($parameters[1]) && is_array($parameters[1]) ? $parameters[1] : $parameters);

			return $this->translator->translate([$message, $count], ...$arguments);
		}

		return $this->translator->translate($message, ...$parameters);
	}
}
