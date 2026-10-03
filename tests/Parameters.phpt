<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/Fixtures.php';

use Bckp\Translator\Nette\Interfaces\Translator;
use Tester\Assert;

use function Bckp\Translator\Nette\Tests\container;

$translator = container()->getByType(Translator::class);
$text = new class implements Stringable {
	#[\Override]
	public function __toString(): string
	{
		return 'text';
	}
};
$key = new class implements Stringable {
	#[\Override]
	public function __toString(): string
	{
		return 'messages.hello';
	}
};
Assert::same('Hello-cs', $translator->translate($key));
Assert::same('1||', $translator->translate('messages.parameters', true, false, null));
Assert::same('text|42|2.5', $translator->translate('messages.parameters', $text, 42, 2.5));
Assert::same('3', $translator->translate('messages.number', 3));
Assert::exception(static fn() => $translator->translate('messages.parameters', []), InvalidArgumentException::class);
Assert::exception(static fn() => $translator->translate('messages.parameters', new stdClass()), InvalidArgumentException::class);
