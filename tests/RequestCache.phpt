<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/Fixtures.php';

use Bckp\Translator\Nette\Diagnostics\TranslatorPanel;
use Bckp\Translator\Nette\Interfaces\Translator;
use Bckp\Translator\Nette\Tests\CountingSource;
use Tester\Assert;

use function Bckp\Translator\Nette\Tests\container;

Assert::same('Hello-cs', container()->getByType(Translator::class)->translate('messages.hello'));
CountingSource::resetCounts();
CountingSource::$version = 'v2';
CountingSource::$text = 'Changed';
$warm = container()->getByType(Translator::class);
Assert::same('Hello-cs', $warm->translate('messages.hello'));
Assert::same(0, CountingSource::$instances);
Assert::same(0, CountingSource::$versionCalls);
Assert::same(0, CountingSource::$loadCalls);

$checked = container(1)->getByType(Translator::class);
Assert::same('Changed-cs', $checked->translate('messages.hello'));
Assert::same(1, CountingSource::$versionCalls);
Assert::same(1, CountingSource::$loadCalls);
for ($i = 0; $i < 100; ++$i) {
	Assert::same('Changed-cs', $checked->translate('messages.hello'));
}
Assert::same(1, CountingSource::$versionCalls);

CountingSource::resetCounts();
$debug = container(0, true);
Assert::same('Changed-cs', $debug->getByType(Translator::class)->translate('messages.hello'));
Assert::same(1, CountingSource::$versionCalls);
Assert::same(0, CountingSource::$loadCalls);
$panel = $debug->getService('translator.diagnostics');
Assert::type(TranslatorPanel::class, $panel);
$html = $panel->getPanel();
Assert::contains('v2', $html);
Assert::same(1, CountingSource::$versionCalls);
Assert::same(0, CountingSource::$loadCalls);
Assert::same('Changed-cs', container(0, true)->getByType(Translator::class)->translate('messages.hello'));
Assert::same(2, CountingSource::$versionCalls);
