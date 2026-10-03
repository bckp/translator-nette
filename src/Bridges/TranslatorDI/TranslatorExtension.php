<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Bridges\TranslatorDI;

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Diagnostics\Diagnostics;
use Bckp\Translator\Interfaces\CatalogueStorage;
use Bckp\Translator\Interfaces\TranslationSource;
use Bckp\Translator\Locale;
use Bckp\Translator\Nette\Diagnostics\TranslatorPanel;
use Bckp\Translator\Nette\Interfaces\CatalogueFactory;
use Bckp\Translator\Nette\Interfaces\TranslationSourceFactory;
use Bckp\Translator\Nette\LazyTranslationSource;
use Bckp\Translator\Nette\LocaleProvider;
use Bckp\Translator\Nette\NetteTranslator;
use Bckp\Translator\Nette\TranslatorProvider;
use Bckp\Translator\PhpCache\PhpCatalogueStorage;
use Bckp\Translator\PluralProvider;
use Bckp\Translator\StringList;
use InvalidArgumentException;
use Latte\Essential\TranslatorExtension as LatteTranslatorExtension;
use LogicException;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Localization\Translator;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Tracy\Bar;
use Tracy\IBarPanel;

final class TranslatorExtension extends CompilerExtension
{
	public function __construct(private readonly string $tempDir) {}

	#[\Override]
	public function getConfig(): TranslatorConfig
	{
		$config = parent::getConfig();

		if (!$config instanceof TranslatorConfig) {
			throw new LogicException('Translator configuration is not initialized.');
		}

		return $config;
	}

	#[\Override]
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'languages' => Expect::listOf('string')
				->min(1)
				->required()
				->transform(self::normalizeLanguages(...)),
			'sources' => Expect::arrayOf('string|Nette\DI\Definitions\Statement', 'string')
				->default(new SourceDefinitions())
				->transform(SourceDefinitions::from(...)),
			'resolvers' => Expect::listOf('string|Nette\DI\Definitions\Statement')
				->default(new ResolverDefinitions())
				->transform(ResolverDefinitions::from(...)),
			'checkProbability' => Expect::float()
				->min(0)
				->max(1)
				->assert(static fn(float $value): bool => is_finite($value), 'Check probability must be finite.')
				->default(0.01),
			'debugMode' => Expect::bool()->nullable()->default(null),
			'debugger' => Expect::bool()->nullable()->default(null),
			'injectLatte' => Expect::bool()->default(true),
			'pluralProvider' => Expect::type('string|Nette\DI\Definitions\Statement')
				->default(PluralProvider::class),
			'storage' => Expect::type('string|Nette\DI\Definitions\Statement')
				->nullable()
				->default(null),
		])->castTo(TranslatorConfig::class);
	}

	#[\Override]
	public function loadConfiguration(): void
	{
		$config = $this->getConfig();
		$debugMode = $this->getContainerBuilder()->parameters['debugMode'] ?? false;

		if (!is_bool($debugMode)) {
			throw new InvalidArgumentException('The application debugMode parameter must be boolean.');
		}

		$config->debugMode ??= $debugMode;
		$config->debugger ??= $config->debugMode;

		$diagnostics = $this->registerDiagnostics();
		$storage = $this->registerStorage();
		$plural = $this->registerPluralProvider();

		$this->registerLocaleProvider();
		$provider = $this->registerTranslator($diagnostics);

		$this->registerSourceFactories();
		$this->registerCatalogueFactories($provider, $storage, $plural, $diagnostics);
	}

	#[\Override]
	public function beforeCompile(): void
	{
		$this->registerTracyPanel();
		$this->registerLatteExtension();
	}

	private function registerDiagnostics(): ?ServiceDefinition
	{
		$config = $this->getConfig();

		if (!$config->debugMode && !$config->debugger) {
			return null;
		}

		$hasPanel = $config->debugger && interface_exists(IBarPanel::class);
		$diagnostics = $this->getContainerBuilder()
			->addDefinition($this->prefix('diagnostics'))
			->setFactory($hasPanel ? TranslatorPanel::class : Diagnostics::class);

		if ($hasPanel) {
			$diagnostics->addSetup('setLanguages', [$this->languagesStatement()]);
		}

		return $diagnostics;
	}

	private function registerStorage(): ServiceDefinition
	{
		$factory = $this->getConfig()->storage
			?? new Statement(PhpCatalogueStorage::class, [$this->tempDir]);

		return $this->getContainerBuilder()
			->addDefinition($this->prefix('storage'))
			->setType(CatalogueStorage::class)
			->setFactory($factory)
			->setAutowired(false);
	}

	private function registerPluralProvider(): ServiceDefinition
	{
		return $this->getContainerBuilder()
			->addDefinition($this->prefix('pluralProvider'))
			->setFactory($this->getConfig()->pluralProvider)
			->setAutowired(false);
	}

	private function registerLocaleProvider(): void
	{
		$arguments = [$this->languagesStatement()];
		$this->getConfig()->resolvers->each(static function (string|Statement $resolver) use (&$arguments): void {
			$arguments[] = is_string($resolver) ? new Statement($resolver) : $resolver;
		});

		$this->getContainerBuilder()
			->addDefinition($this->prefix('localeProvider'))
			->setFactory(LocaleProvider::class, $arguments);
	}

	private function registerTranslator(?ServiceDefinition $diagnostics): ServiceDefinition
	{
		$builder = $this->getContainerBuilder();
		$provider = $builder->addDefinition($this->prefix('translatorProvider'))
			->setFactory(TranslatorProvider::class, [$diagnostics])
			->setAutowired(false);

		$builder->addDefinition($this->prefix('translator'))
			->setFactory(NetteTranslator::class, [
				$provider,
				'@' . $this->prefix('localeProvider'),
			]);

		return $provider;
	}

	private function registerSourceFactories(): void
	{
		$this->getConfig()->sources->each(function (SourceDefinition $source): void {
			$factory = $this->getContainerBuilder()
				->addFactoryDefinition($this->prefix('source.' . $source->id))
				->setImplement(TranslationSourceFactory::class)
				->setAutowired(false);

			$factory->getResultDefinition()
				->setType(TranslationSource::class)
				->setFactory($source->factory)
				->setAutowired(false);
		});
	}

	private function registerCatalogueFactories(
		ServiceDefinition $provider,
		ServiceDefinition $storage,
		ServiceDefinition $plural,
		?ServiceDefinition $diagnostics,
	): void {
		$config = $this->getConfig();
		$config->languages->each(function (string $locale) use ($provider, $storage, $plural, $diagnostics, $config): void {
			$factory = $this->getContainerBuilder()
				->addFactoryDefinition($this->prefix('catalogue.' . $locale))
				->setImplement(CatalogueFactory::class)
				->setAutowired(false);

			$catalogue = $factory->getResultDefinition()
				->setFactory(CatalogueBuilder::class, [
					$storage,
					$locale,
					$plural,
					$this->prefix('catalogue'),
					$diagnostics,
				])
				->setAutowired(false)
				->addSetup('setDebugMode', [$config->debugMode])
				->addSetup('setCheckProbability', [$config->checkProbability]);

			$this->addCatalogueSources($catalogue);
			$provider->addSetup('addCatalogueFactory', [$locale, $factory]);
		});
	}

	private function addCatalogueSources(ServiceDefinition $catalogue): void
	{
		$this->getConfig()->sources->each(function (SourceDefinition $source) use ($catalogue): void {
			$factory = $this->getContainerBuilder()
				->getDefinition($this->prefix('source.' . $source->id));

			$catalogue->addSetup('addSource', [
				$source->id,
				new Statement(LazyTranslationSource::class, [$factory]),
			]);
		});
	}

	private function registerTracyPanel(): void
	{
		$builder = $this->getContainerBuilder();

		if (!$this->getConfig()->debugger
			|| !interface_exists(IBarPanel::class)
			|| !$builder->hasDefinition($this->prefix('diagnostics'))) {
			return;
		}

		$bar = $builder->getByType(Bar::class);

		if ($bar === null) {
			return;
		}

		$provider = $builder->getDefinition($this->prefix('translatorProvider'));

		if (!$provider instanceof ServiceDefinition) {
			throw new LogicException('Translator provider must be a service definition.');
		}

		$provider->addSetup('@' . $bar . '::addPanel', [
			$builder->getDefinition($this->prefix('diagnostics')),
		]);
	}

	private function registerLatteExtension(): void
	{
		$builder = $this->getContainerBuilder();

		if (!$this->getConfig()->injectLatte || !$builder->hasDefinition('latte.latteFactory')) {
			return;
		}

		$factory = $builder->getDefinition('latte.latteFactory');

		if (!$factory instanceof FactoryDefinition) {
			throw new LogicException('Latte must expose a factory definition.');
		}

		$factory->getResultDefinition()->addSetup('addExtension', [
			new Statement(LatteTranslatorExtension::class, [new Reference(Translator::class)]),
		]);
	}

	private function languagesStatement(): Statement
	{
		$languages = [];
		$this->getConfig()->languages->each(static function (string $locale) use (&$languages): void {
			$languages[] = $locale;
		});

		return new Statement(StringList::class, $languages);
	}

	private static function normalizeLanguages(mixed $value): StringList
	{
		if (!is_array($value)) {
			throw new InvalidArgumentException('Languages must be a list.');
		}

		$languages = new StringList();

		foreach ($value as $locale) {
			if (!is_string($locale)) {
				throw new InvalidArgumentException('Language codes must be strings.');
			}

			$languages->add(Locale::normalize($locale));
		}

		return $languages;
	}
}
