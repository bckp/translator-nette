<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Tests\Diagnostics;

require __DIR__ . '/../bootstrap.php';

use Bckp\Translator\Nette\Diagnostics\TranslatorPanel;
use Bckp\Translator\Nette\Resolvers\Resolver;
use Mockery;
use Tester\Assert;
use Tester\TestCase;
use function get_defined_vars;

class TranslatorPanelTest extends TestCase
{
	private TranslatorPanel $panel;

	protected function setUp(): void
	{
		// Vytvoříme partial mock, abychom mohli přepsat metody z rodičovské třídy
		$this->panel = Mockery::mock(TranslatorPanel::class)->makePartial();

		// Výchozí hodnoty pro mockované metody z Diagnostics
		$this->panel->shouldReceive('getLocale')->andReturn('cs')->byDefault();
		$this->panel->shouldReceive('getWarnings')->andReturn([])->byDefault();
		$this->panel->shouldReceive('getUntranslated')->andReturn([])->byDefault();
	}

	public function testSetResolvers(): void
	{
		$resolver1 = Mockery::mock(Resolver::class);
		$resolver2 = Mockery::mock(Resolver::class);

		$languages = ['cs', 'en', 'de'];

		$this->panel->setResolvers($languages, $resolver1, $resolver2);

		// Kontrola nastavení resolverů
		$reflection = new \ReflectionProperty($this->panel, 'resolvers');
		$reflection->setAccessible(true);
		$resolvers = $reflection->getValue($this->panel);

		Assert::count(2, $resolvers);
		Assert::same($resolver1, $resolvers[0]);
		Assert::same($resolver2, $resolvers[1]);

		// Kontrola nastavení jazyků
		$reflection = new \ReflectionProperty($this->panel, 'languages');
		$reflection->setAccessible(true);
		$panelLanguages = $reflection->getValue($this->panel);

		Assert::same($languages, $panelLanguages);
	}

	public function testGetTabWithoutErrors(): void
	{
		$this->panel->shouldReceive('getWarnings')->andReturn([]);
		$this->panel->shouldReceive('getUntranslated')->andReturn([]);

		// Override metody Helpers::capture, která je v getTab použita
		$this->overrideHelpersCapture();

		$result = $this->panel->getTab();
		Assert::type('string', $result);

		// Kontrola, že byla metoda volána s očekávanými parametry
		Assert::true(isset($GLOBALS['helpers_capture_data']), 'Helpers::capture nebyla volána');
		Assert::contains('009688', $GLOBALS['helpers_capture_data']['color'], 'Barva pro stav bez chyb není správná');
		Assert::same('CS', $GLOBALS['helpers_capture_data']['locale'], 'Locale není správně zobrazen');
	}

	public function testGetTabWithErrors(): void
	{
		$this->panel->shouldReceive('getWarnings')->andReturn(['error1']);
		$this->panel->shouldReceive('getUntranslated')->andReturn([]);

		$this->overrideHelpersCapture();

		$result = $this->panel->getTab();
		Assert::type('string', $result);

		Assert::true(isset($GLOBALS['helpers_capture_data']), 'Helpers::capture nebyla volána');
		Assert::contains('B71C1C', $GLOBALS['helpers_capture_data']['color'], 'Barva pro stav s chybami není správná');
	}

	public function testGetTabWithUntranslated(): void
	{
		$this->panel->shouldReceive('getWarnings')->andReturn([]);
		$this->panel->shouldReceive('getUntranslated')->andReturn(['untranslated1']);

		$this->overrideHelpersCapture();

		$result = $this->panel->getTab();
		Assert::type('string', $result);

		Assert::true(isset($GLOBALS['helpers_capture_data']), 'Helpers::capture nebyla volána');
		Assert::contains('B71C1C', $GLOBALS['helpers_capture_data']['color'], 'Barva pro stav s nepřeloženými texty není správná');
	}

	public function testGetPanel(): void
	{
		$this->panel->shouldReceive('getWarnings')->andReturn(['error1', 'error2']);
		$this->panel->shouldReceive('getUntranslated')->andReturn(['untranslated1']);

		$resolver1 = Mockery::mock(Resolver::class);
		$languages = ['cs', 'en', 'de'];
		$this->panel->setResolvers($languages, $resolver1);

		$this->overrideHelpersCapture();

		$result = $this->panel->getPanel();
		Assert::type('string', $result);

		Assert::true(isset($GLOBALS['helpers_capture_data']), 'Helpers::capture nebyla volána');
		Assert::same('CS', $GLOBALS['helpers_capture_data']['locale']);
		Assert::same(['error1', 'error2'], $GLOBALS['helpers_capture_data']['errors']);
		Assert::same(2, $GLOBALS['helpers_capture_data']['countErrors']);
		Assert::true($GLOBALS['helpers_capture_data']['hasErrors']);
		Assert::same(['untranslated1'], $GLOBALS['helpers_capture_data']['untranslated']);
		Assert::same(1, $GLOBALS['helpers_capture_data']['countUntranslated']);
		Assert::true($GLOBALS['helpers_capture_data']['hasUntranslated']);
		Assert::same($languages, $GLOBALS['helpers_capture_data']['languages']);
	}

	/**
	 * Pomocná metoda pro nahrazení Helpers::capture callback funkcí,
	 * která zachytí proměnné pro pozdější kontrolu
	 */
	private function overrideHelpersCapture(): void
	{
		// Přepíšeme globální funkci pomocí runkit nebo podobné techniky
		// V reálném prostředí by toto vyžadovalo dodatečné nástroje
		// Pro účely testu simulujeme výstup
		$GLOBALS['helpers_capture_data'] = [];

		$reflection = new \ReflectionProperty(\Nette\Utils\Helpers::class, 'capture');
		$reflection->setAccessible(true);
		$reflection->setValue(null, function () use (&$GLOBALS) {
			// Ukládáme hodnoty proměnných pro pozdější kontrolu
			$GLOBALS['helpers_capture_data'] = get_defined_vars();
			// Vracíme nějaký testovací výstup
			return 'Test output';
		});
	}

	protected function tearDown(): void
	{
		Mockery::close();
		unset($GLOBALS['helpers_capture_data']);
	}
}

// Spuštění testu
(new TranslatorPanelTest())->run();
