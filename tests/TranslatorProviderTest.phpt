<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Tests;

require __DIR__ . '/bootstrap.php';

use Bckp\Translator\Catalogue;
use Bckp\Translator\CatalogueBuilder;
use Bckp\Translator\Exceptions\TranslatorException;
use Bckp\Translator\Interfaces\Diagnostics;
use Bckp\Translator\Nette\TranslatorProvider;
use Bckp\Translator\Plural;
use Bckp\Translator\Translator;
use Mockery;
use Tester\Assert;
use Tester\TestCase;

class TranslatorProviderTest extends TestCase
{
	private TranslatorProvider $provider;

	protected function setUp(): void
	{
		$this->provider = new TranslatorProvider(null);
	}

	public function testAddCatalogueBuilder(): void
	{
		$builder = Mockery::mock(CatalogueBuilder::class);
		$builder->shouldReceive('getLocale')->andReturn('cs');
		$builder->shouldReceive('compile')->andReturn($this->getCatalogue('cs', 123, ['hello' => 'ahoj']));

		$this->provider->addCatalogueBuilder($builder);

		$translator = $this->provider->getTranslator('cs');
		Assert::type(Translator::class, $translator);
	}

	public function testGetTranslatorWithInvalidLocale(): void
	{
		Assert::exception(function () {
			$this->provider->getTranslator('unknown-locale');
		}, TranslatorException::class, 'Locale unknown-locale not found, available locales: ');
	}

	public function testNormalizeLocale(): void
	{
		$reflection = new \ReflectionMethod($this->provider, 'normalizeLocale');
		$reflection->setAccessible(true);

		$result = $reflection->invoke($this->provider, 'CS');
		Assert::same('cs', $result);

		$result = $reflection->invoke($this->provider, 'en-US');
		Assert::same('en-us', $result);
	}

	public function testMultipleCatalogueBuilders(): void
	{
		$builder1 = Mockery::mock(CatalogueBuilder::class);
		$builder1->shouldReceive('getLocale')->andReturn('cs');
		$builder1->shouldReceive('compile')->andReturn($this->getCatalogue('cs', 123, ['hello' => 'ahoj']));

		$builder2 = Mockery::mock(CatalogueBuilder::class);
		$builder2->shouldReceive('getLocale')->andReturn('en');
		$builder2->shouldReceive('compile')->andReturn($this->getCatalogue('cs', 123, ['hello' => 'hello']));

		$this->provider->addCatalogueBuilder($builder1);
		$this->provider->addCatalogueBuilder($builder2);

		$translatorCs = $this->provider->getTranslator('cs');
		$translatorEn = $this->provider->getTranslator('en');

		Assert::type(Translator::class, $translatorCs);
		Assert::type(Translator::class, $translatorEn);
		Assert::notSame($translatorCs, $translatorEn);
	}

	public function testTranslatorCaching(): void
	{
		$builder = Mockery::mock(CatalogueBuilder::class);
		$builder->shouldReceive('getLocale')->andReturn('cs');
		$builder->shouldReceive('compile')->once()->andReturn($this->getCatalogue('cs', 123, ['hello' => 'ahoj']));

		$this->provider->addCatalogueBuilder($builder);

		$translator1 = $this->provider->getTranslator('cs');
		$translator2 = $this->provider->getTranslator('cs');

		Assert::same($translator1, $translator2);
	}

	public function testCaseInsensitiveLocale(): void
	{
		$builder = Mockery::mock(CatalogueBuilder::class);
		$builder->shouldReceive('getLocale')->andReturn('cs');
		$builder->shouldReceive('compile')->once()->andReturn($this->getCatalogue('cs', 123, ['hello' => 'ahoj']));

		$this->provider->addCatalogueBuilder($builder);

		$translator1 = $this->provider->getTranslator('cs');
		$translator2 = $this->provider->getTranslator('CS');

		Assert::same($translator1, $translator2);
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}

	protected function getCatalogue(string $lang, int $build, array $messages): Catalogue
	{
		return new class ($lang, $build, $messages) extends Catalogue {
			public function __construct(string $lang, int $build, array $messages)
			{
				parent::__construct($lang, $build);
				self::$messages = $messages;
			}

			public function plural(int $n): Plural
			{
				return Plural::Other;
			}
		};
	}
}

// Spuštění testu
(new TranslatorProviderTest())->run();
