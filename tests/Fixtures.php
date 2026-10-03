<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Tests;

use Bckp\Translator\Interfaces\TranslationSource;
use Bckp\Translator\MessageCatalogue;
use Bckp\Translator\Nette\Bridges\TranslatorDI\TranslatorExtension;
use Latte\Engine;
use Latte\Loaders\StringLoader;
use Nette\DI\Compiler;
use Nette\DI\Container;
use Nette\DI\Definitions\Statement;
use RuntimeException;
use Tracy\Bar;

final class CountingSource implements TranslationSource
{
	public static int $instances = 0;

	public static int $versionCalls = 0;

	public static int $loadCalls = 0;

	public static string $version = 'v1';

	public static string $text = 'Hello';

	public static bool $fail = false;

	public function __construct()
	{
		++self::$instances;
	}

	public static function resetCounts(): void
	{
		self::$instances = 0;
		self::$versionCalls = 0;
		self::$loadCalls = 0;
	}

	#[\Override]
	public function getVersion(string $locale): string
	{
		++self::$versionCalls;

		return self::$version;
	}

	#[\Override]
	public function __invoke(string $locale): MessageCatalogue
	{
		++self::$loadCalls;

		if (self::$fail) {
			throw new RuntimeException('Source failed.');
		}

		return new MessageCatalogue()->add('messages.hello', self::$text . '-' . $locale)
			->add('messages.parameters', '%s|%s|%s')
			->add('messages.number', '%d');
	}
}

interface LatteFactory
{
	public function create(): Engine;
}

function container(float $probability = 0.0, bool $debug = false): Container
{
	static $sequence = 0;
	$class = 'TestTranslatorContainer' . ++$sequence;
	$compiler = new Compiler();
	$compiler->setClassName($class);
	$compiler->addExtension('translator', new TranslatorExtension(TEMP_DIR . '/catalogues'));
	$latte = $compiler->getContainerBuilder()->addFactoryDefinition('latte.latteFactory')->setImplement(LatteFactory::class);
	$latte->getResultDefinition()->setFactory(Engine::class)
		->addSetup('setLoader', [new Statement(StringLoader::class)]);

	if ($debug) {
		$compiler->getContainerBuilder()->addDefinition('tracy.bar')->setFactory(Bar::class);
	}
	$compiler->addConfig([
		'parameters' => ['debugMode' => $debug],
		'translator' => [
			'languages' => ['cs', 'en'],
			'sources' => ['application' => CountingSource::class],
			'checkProbability' => $probability,
		],
	]);
	eval($compiler->compile());

	return new $class();
}
