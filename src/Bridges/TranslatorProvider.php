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

use Bckp\Translator\Builder\Catalogue;
use Bckp\Translator\IDiagnostics;
use Bckp\Translator\Translator;
use Bckp\Translator\TranslatorException;
use Nette\Localization\ITranslator;

/**
 * Class TranslatorProvider
 *
 * @package Bckp\Translator\Nette\Bridges
 */
final class TranslatorProvider {

	/**
	 * @var ITranslator[]
	 */
	protected $translators = [];

	/**
	 * @var Catalogue[]
	 */
	protected $catalogues = [];

	/**
	 * @var array
	 */
	protected $languages = [];

	/**
	 * @var IDiagnostics|null
	 */
	protected $diagnostics;

	/**
	 * @var string
	 */
	private $prototype;

	/**
	 * TranslatorProvider constructor.
	 *
	 * @param array $languages
	 * @param string $prototype
	 * @param IDiagnostics|null $diagnostics
	 */
	public function __construct(array $languages, string $prototype, IDiagnostics $diagnostics = null) {
		$this->languages = $languages;
		$this->diagnostics = $diagnostics;
		$this->prototype = $prototype;
	}

	/**
	 * @param string $locale
	 * @return ITranslator
	 * @throws \Throwable
	 */
	public function getTranslator(string $locale): ITranslator {
		$locale = strtolower($locale);
		if (!isset($this->translators[$locale]))
			$this->translators[$locale] = $this->createTranslator($locale);
		return $this->translators[$locale];
	}

	/**
	 * @param string $locale
	 * @return ITranslator
	 * @throws \Throwable
	 */
	protected function createTranslator(string $locale): ITranslator {
		if (!isset($this->catalogues[$locale]))
			throw new TranslatorException("Language {$locale} requested, but corresponding catalogue missing.");

		$translator = new Translator($this->catalogues[$locale]->compile(), $this->diagnostics);
		return new $this->prototype($translator);
	}

	/**
	 * @param string $locale
	 * @param Catalogue $builder
	 * @return void
	 */
	public function addCatalogue(string $locale, Catalogue $builder): void {
		$locale = strtolower($locale);
		$this->catalogues[$locale] = $builder;
	}
}

