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

namespace Bckp\Translator\Nette\Bridges\TranslatorDI;

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Nette\Diagnostics\TranslatorPanel;
use Bckp\Translator\Nette\LocaleProvider;
use Bckp\Translator\Nette\NetteTranslator;
use Bckp\Translator\Nette\Resolvers\LocaleResolver;
use Bckp\Translator\Nette\TranslatorProvider;
use Bckp\Translator\PluralProvider;
use Latte\Essential\TranslatorExtension as LatteTranslatorExtension;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Localization\Translator;
use Nette\Schema\Context;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Finder;

/**
 * @api
 * @method object{languages: string[], resolvers: LocaleResolver[], pluralProvider: class-string, paths: string[], debugger: bool, injectLatte: bool } getConfig()
 */
final class TranslatorExtension extends CompilerExtension
{
	public function __construct(
		private readonly string $tempDir
	) {
		// Create temp directory
		if (!is_dir($this->tempDir) && !mkdir($concurrentDirectory = $this->tempDir, 0o777, true)
			&& !is_dir(
				$concurrentDirectory
			)) {
			throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
		}
	}

	#[\Override]
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'languages' => Expect::listOf('string')->transform(static function (array $languages, Context $context): ?array {
				$languages = array_map('strtolower', $languages);
				$languages = array_unique($languages);

				foreach ($languages as $language) {
					if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $language)) {
						$context->addError("Invalid language code: $language", 'translator.languages.validation');
						return null;
					}
				}

				return $languages;
			})->default([])->required(),
			'resolvers' => Expect::listOf('string|Nette\DI\Definitions\Statement')->default([]),
			'pluralProvider' => Expect::type('string|Nette\DI\Definitions\Statement')->default(PluralProvider::class),
			'paths' => Expect::arrayOf('string')->required(),
			'debugger' => Expect::bool()->default('%debugMode%'),
			'injectLatte' => Expect::bool()->default(true),
		]);
	}

	#[\Override]
	public function loadConfiguration(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		// Setup translator
		if ($config->debugger) {
			$builder->addDefinition($this->prefix('diagnostics'))
					->setFactory(TranslatorPanel::class)
					->addSetup('setResolvers', [$config->languages, ...$config->resolvers]);

		}

		// Setup LocaleProvider
		$builder->addDefinition($this->prefix('localeProvider'))
			->setFactory(LocaleProvider::class, [$config->languages, ...$config->resolvers]);

		// Translator provider
		$translatorProvider = $builder->addDefinition($this->prefix('translatorProvider'))
			->setFactory(TranslatorProvider::class)
			->setAutowired(false);

		// Plural provider
		$pluralProvider = $builder->addDefinition($this->prefix('pluralProvider'))
			->setFactory($config->pluralProvider)
			->setAutowired(false);

		// Translator itself
		$builder->addDefinition($this->prefix('translator'))
			->setFactory(NetteTranslator::class, [
				'@' . $this->prefix('translatorProvider'),
				'@' . $this->prefix('localeProvider'),
			])
			->setAutowired();

		// Catalogues
		foreach ($config->languages as $language) {
			$translatorProvider->addSetup('addCatalogueBuilder', [
				$builder->addDefinition($this->prefix('catalogue.' . $language))
						->setFactory(CatalogueBuilder::class, [
							$pluralProvider,
							$this->tempDir,
							$language,
						]),
			]);
		}
	}

	#[\Override]
	public function beforeCompile(): void
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		# Setup catalogue files
		foreach ($config->languages as $language) {
			/** @var ServiceDefinition $catalogue */
			$catalogue = $builder->getDefinition($this->prefix('catalogue.' . $language));

			foreach (Finder::findFiles("*.{$language}.neon")->in(...$config->paths) as $file) {
				$catalogue->addSetup('addFile', [$file->getPathname()]);
			}
		}

		# Setup bar
		if (
			$config->debugger
			&& $builder->hasDefinition($this->prefix('diagnostics'))
		) {
			/** @var ServiceDefinition $translatorProvider */
			$translatorProvider = $builder->getDefinition($this->prefix('translatorProvider'));
			$translatorProvider->addSetup('@Tracy\Bar::addPanel', [$builder->getDefinition($this->prefix('diagnostics'))]);
		}

		# Auto setup nette translator to latte
		if ($config->injectLatte && $builder->hasDefinition('latte.latteFactory')) {
			/** @var FactoryDefinition $latteFactory */
			$latteFactory = $builder->getDefinition('latte.latteFactory');
			$latteFactory->getResultDefinition()
				->addSetup('addExtension', [
					new Statement(LatteTranslatorExtension::class, [new Reference(Translator::class)]),
				])
				->setAutowired(false);
		}
	}

}
