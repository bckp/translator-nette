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

namespace Bckp\Translator\Nette\Bridges;

use Nette\Application\UI\ITemplate;
use Nette\Localization\ITranslator;

/**
 * Trait TranslatorAwarePresenter
 *
 * @package Bckp\Translator\Nette\Bridges
 * @property array $onStartup
 * @property array $onRender
 * @property array $onBeforeRender
 * @property-read ITemplate $template
 */
trait TranslatorAwarePresenter {
	/**
	 * @var string
	 * @persistent
	 */
	public $lang;

	/** @var ITranslator */
	protected $translator;

	/**
	 * @param TranslatorProvider $provider
	 */
	public function injectTranslator(TranslatorProvider $provider) {
		$this->onStartup[] = function () use ($provider) {
			$this->translator = $provider->getTranslator($this->lang);
		};
		$this->onRender[] = function () {
			$this->template->setTranslator($this->translator);
		};
	}
}
