<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Tests;

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\Translator;
use Bckp\Translator\Nette\LocaleProvider;
use Bckp\Translator\Nette\NetteTranslator;
use Bckp\Translator\Nette\TranslatorProvider;
use Mockery;
use Tester\Assert;
use Tester\TestCase;

class NetteTranslatorTest extends TestCase
{
	public function testNetteTranslatorTest(): void
	{
		$translatorProvider = Mockery::mock(TranslatorProvider::class);
		$localeProvider = Mockery::mock(LocaleProvider::class);
		$translatorMock = Mockery::mock(Translator::class);

		$translator = new NetteTranslator($translatorProvider, $localeProvider);

		$translatorProvider->shouldReceive('getTranslator')->with('cs')->andReturn($translatorMock);
		$localeProvider->shouldReceive('resolve')->andReturn('cs');

		$translatorMock->shouldReceive('translate')->withArgs(function($message, ...$args) {
			Assert::same('test.to.translate', $message);
			Assert::same([
				0 => 'text',
				1 => '',
				2 => 1,
				3 => 1.1
			], $args);

			return true;
		})->andReturn('ok');

		Assert::same('ok', $translator->translate('test.to.translate', 'text', null, 1, 1.1));
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

// Spuštění testu
(new NetteTranslatorTest())->run();
