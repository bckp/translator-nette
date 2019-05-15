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
use Bckp\Translator\Nette\Diagnostics\Panel;
use Bckp\Translator\Nette\LangResolver;
use Bckp\Translator\Nette\Translator;
use Bckp\Translator\PathInvalidException;
use Bckp\Translator\PluralProvider;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\Utils\Finder;

/**
 * Class TranslatorExtension
 *
 * @package Bckp\Translator\Nette\Bridges
 * @property-read \stdClass $config
 */
class TranslatorExtension extends CompilerExtension {
	/**
	 * @var string
	 */
	private $tempDir;

	/**
	 * @var array|null
	 */
	private $scanDirs;

	/**
	 * @var bool|null
	 */
	private $debugMode;

	/**
	 * TranslatorExtension constructor.
	 *
	 * @param string $tempDir
	 * @param array|null $scanDirs
	 * @param bool|null $debugMode
	 */
	public function __construct(string $tempDir, array $scanDirs = null, bool $debugMode = null) {
		$this->tempDir = $tempDir;
		$this->scanDirs = $scanDirs;
		$this->debugMode = $debugMode;

		// Create temp directory
		if (!is_dir($this->tempDir)) {
			@mkdir($this->tempDir, 0777, true);
			if (!is_dir($this->tempDir))
				throw new PathInvalidException("Failed to create temp dir on path '{$this->tempDir}'.");
		}
	}

	/**
	 * @return Nette\Schema\Schema
	 */
	public function getConfigSchema(): Nette\Schema\Schema {
		return Nette\Schema\Expect::structure([
			'path' => Nette\Schema\Expect::arrayOf('string')->default($this->scanDirs),
			'debugger' => Nette\Schema\Expect::bool()->default($this->debugMode),
			'languages' => Nette\Schema\Expect::array()->default([]),
			'prototype' => Nette\Schema\Expect::string(Translator::class),
			'resolver' => Nette\Schema\Expect::bool(false),
		]);
	}

	/**
	 * Load configuration
	 */
	public function loadConfiguration() {
		$config = $this->config;
		$plural = new PluralProvider;
		$builder = $this->getContainerBuilder();

		// Panel if in debug
		if ($config->debugger)
			$builder->addDefinition($this->prefix('diagnostics'))->setFactory(Panel::class);

		// Resolver if configured
		if ($config->resolver)
			$builder->addDefinition($this->prefix('languageResolver'))->setFactory(LangResolver::class, [$config->languages]);

		// Provider
		$builder->addDefinition($this->prefix('translatorProvider'))->setFactory(TranslatorProvider::class, [$config->languages, $config->prototype]);

		// Add catalogues, unique for lang
		foreach ($this->uniqueLanguages($config->languages) as $locale) {
			$builder->addDefinition($this->prefix($locale . 'Catalogue'))->setFactory(Catalogue::class, [
				$plural,
				$this->tempDir,
				$locale,
			])->setTags([$this->prefix('catalogue')]);
		}
	}

	/**
	 * @param array $languages
	 * @return array
	 */
	protected function uniqueLanguages(array $languages): array {
		return array_unique(array_map('strtolower', $languages));
	}

	public function beforeCompile() {
		$config = $this->config;
		$builder = $this->getContainerBuilder();

		/** @var Nette\DI\Definitions\ServiceDefinition $provider */
		$provider = $builder->getDefinition($this->prefix('translatorProvider'));
		foreach ($this->uniqueLanguages($config->languages) as $locale) {
			/** @var Nette\DI\Definitions\ServiceDefinition $catalogue */
			$catalogue = $builder->getDefinition($this->prefix($locale . 'Catalogue'));

			// Enable debug mode for catalogue
			if ($config->debugger)
				$catalogue->addSetup('setDebugMode', [true]);

			// Add resource files
			foreach ($this->getCatalogueResources($locale) as $file)
				$catalogue->addSetup('addFile', [$file->getPathname()]);

			// Add catalogue to provider
			$provider->addSetup('addCatalogue', [$locale, $catalogue]);
		}

		if ($config->resolver) {
			$resolver = $builder->getDefinition($this->prefix('languageResolver'));
			/** @var Nette\DI\Definitions\ServiceDefinition $application */
			$application = $builder->getDefinitionByType(Nette\Application\Application::class);
			$application->addSetup('$onRequest[]', [[$resolver, 'handleRequest']]);
		}
	}

	protected function getCatalogueResources(string $locale): array {
		$return = [];
		foreach (Finder::findFiles('*.' . strtolower($locale) . '.neon')->from(...$this->config->path) as $file) {
			$return[] = $file;
		}
		return $return;
	}
}
