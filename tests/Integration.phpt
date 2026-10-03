<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/Fixtures.php';

use Bckp\Translator\Nette\Interfaces\Translator;
use Bckp\Translator\Nette\Tests\CountingSource;
use Bckp\Translator\Nette\TranslatorProvider;
use Tester\Assert;

use function Bckp\Translator\Nette\Tests\container;

$container = container();
$translator = $container->getByType(Translator::class);
$latte = $container->getService('latte.latteFactory')->create();
Assert::same(0, CountingSource::$instances);
Assert::same('Hello-cs', $translator->translate('messages.hello'));
Assert::same('Hello-cs', $latte->renderToString("{_'messages.hello'}"));
Assert::same(1, CountingSource::$instances);
Assert::same(1, CountingSource::$versionCalls);
Assert::same(1, CountingSource::$loadCalls);
$provider = $container->getService('translator.translatorProvider');
Assert::type(TranslatorProvider::class, $provider);
$raw = $provider->getTranslator('cs');

CountingSource::$text = 'Forced';
$translator->forceRecompile();
Assert::same($raw, $provider->getTranslator('cs'));
Assert::same('Forced-cs', $raw->translate('messages.hello'));
Assert::same('Forced-cs', $translator->translate('messages.hello'));
Assert::same('Forced-cs', $latte->renderToString("{_'messages.hello'}"));
Assert::same(2, CountingSource::$versionCalls);
Assert::same(2, CountingSource::$loadCalls);
CountingSource::$text = 'Provider';
$provider->forceRecompile('cs');
Assert::same('Provider-cs', $translator->translate('messages.hello'));
Assert::same('Provider-cs', $latte->renderToString("{_'messages.hello'}"));

CountingSource::$text = 'English';
$translator->forceRecompile('en');
Assert::same('English-en', $provider->getTranslator('en')->translate('messages.hello'));
Assert::same('Provider-cs', $translator->translate('messages.hello'));
Assert::same('cs', $translator->getLocale());
CountingSource::$fail = true;
Assert::exception(static fn() => $translator->forceRecompile(), RuntimeException::class);
Assert::same('Provider-cs', $translator->translate('messages.hello'));
Assert::same('Provider-cs', $latte->renderToString("{_'messages.hello'}"));
