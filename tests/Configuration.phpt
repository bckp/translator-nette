<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/Fixtures.php';

use Bckp\Translator\Neon\NeonSource;
use Bckp\Translator\Nette\Bridges\TranslatorDI\TranslatorExtension;
use Bckp\Translator\Nette\Interfaces\Translator;
use Bckp\Translator\Nette\Tests\CountingSource;
use Bckp\Translator\Storage\MemoryStorage;
use Nette\DI\Compiler;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Tester\Assert;

$extension = new TranslatorExtension(TEMP_DIR . '/cache');
$schema = $extension->getConfigSchema();
Assert::exception(static fn() => (new Processor())->process($schema, ['languages' => [], 'debugMode' => false, 'debugger' => false]), ValidationException::class);
Assert::exception(static fn() => (new Processor())->process($schema, ['languages' => ['cs'], 'checkProbability' => -0.1, 'debugMode' => false, 'debugger' => false]), ValidationException::class);
Assert::exception(static fn() => (new Processor())->process($schema, ['languages' => ['cs'], 'checkProbability' => NAN]), ValidationException::class);
$config = (new Processor())->process($schema, ['languages' => ['CS_CZ', 'cs-cz'], 'debugMode' => false, 'debugger' => false]);
Assert::same('cs-cz', $config->languages->first());
Assert::same(1, $config->languages->count());

$path = TEMP_DIR . '/locales';
mkdir($path);
file_put_contents($path . '/messages.cs.neon', "hello: Files\n");
$compiler = new Compiler();
$compiler->setClassName('TestSourcesContainer');
$compiler->addExtension('translator', new TranslatorExtension(TEMP_DIR . '/catalogues'));
$compiler->getContainerBuilder()->addDefinition('applicationTranslations')->setFactory(CountingSource::class)->setAutowired(false);
$compiler->addConfig([
	'parameters' => ['debugMode' => false],
	'translator' => [
		'languages' => ['cs', 'en'],
		'sources' => ['files' => new Statement(NeonSource::class, [$path]), 'database' => '@applicationTranslations'],
		'injectLatte' => false,
	],
]);
eval($compiler->compile());
$container = new TestSourcesContainer();
$translator = $container->getByType(Translator::class);
Assert::same(0, CountingSource::$instances);
Assert::same('Hello-cs', $translator->translate('messages.hello'));
Assert::same(1, CountingSource::$loadCalls);

$compiler = new Compiler();
$compiler->setClassName('TestEmptyContainer');
$compiler->addExtension('translator', new TranslatorExtension('/unused/cache'));
$compiler->addConfig([
	'parameters' => ['debugMode' => true],
	'translator' => [
		'languages' => ['en'],
		'debugMode' => false,
		'debugger' => false,
		'injectLatte' => false,
		'storage' => MemoryStorage::class,
	],
]);
eval($compiler->compile());
$empty = new TestEmptyContainer();
Assert::same('unknown', $empty->getByType(Translator::class)->translate('unknown'));
Assert::false($empty->hasService('translator.diagnostics'));
