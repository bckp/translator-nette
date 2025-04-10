<?php declare(strict_types=1);

namespace Bckp\Translator\Nette\Bridges\TranslatorDI;

use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Nette\LocaleProvider;
use Bckp\Translator\Nette\TranslatorProvider;
use Bckp\Translator\PluralProvider;
use Nette;
use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;

class TranslatorExtension extends CompilerExtension
{
	public function __construct(
		private readonly string $tempDir,
		private readonly bool $debugMode = false,
	) {
		// Create temp directory
		if (!is_dir($this->tempDir) && !mkdir($concurrentDirectory = $this->tempDir, 0o777, true)
		    && !is_dir(
				$concurrentDirectory
			)) {
			throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
		}
	}

	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'languages' => Expect::listOf('string')->required(),
			'resolvers' => Expect::listOf('string|Nette\DI\Definitions\Statement')->default([]),
			'pluralProvider' => Expect::type('string|Nette\DI\Definitions\Statement')->default(PluralProvider::class),
			'path' => Expect::arrayOf('string')->required(),
			'debugger' => Expect::bool()->default($this->debugMode),
			'injectLatte' => Expect::bool()->default(true),
		]);
	}

	public function loadConfiguration()
	{
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();

		if ($config->debugger) {
			// Setup extension for Tracy Panel if exists
			//$builder->addDefinition($this->prefix('diagnostics'))->setFactory(Panel::class);
		}

		// Setup LocaleProvider
		$builder->addDefinition($this->prefix('localeProvider'))
			->setFactory(LocaleProvider::class, [$config->languages, $this->config->resolvers]);

		foreach($config->languages as $language) {
			$builder->addDefinition($this->prefix('catalogue.' . $language))
				->setFactory(CatalogueBuilder::class, [
					$config->pluralProvider,
					$config->path,
					$language,
				])
				->setTags([$this->prefix('catalogue')]);
		}

		$builder->addDefinition($this->prefix('TranslatorProvider'))
			->setFactory(TranslatorProvider::class)
			->setAutowired(false);
	}

}
