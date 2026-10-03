<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette;

use Bckp\Translator\Nette\Interfaces;
use Bckp\Translator\Translator;
use InvalidArgumentException;
use Stringable;

final class NetteTranslator implements Interfaces\Translator
{
	private ?Translator $translator = null;

	public function __construct(
		private readonly TranslatorProvider $translatorProvider,
		private readonly LocaleProvider $localeProvider,
	) {
	}

	#[\Override]
	public function getLocale(): string
	{
		return $this->localeProvider->resolve();
	}

	#[\Override]
	public function translate(Stringable|string $message, mixed ...$parameters): string
	{
		$normalized = [];
		foreach ($parameters as $key => $value) {
			$normalized[$key] = $this->parameter($value);
		}

		return ($this->translator ??= $this->translatorProvider->getTranslator($this->getLocale()))
			->translate($message, ...$normalized);
	}

	#[\Override]
	public function forceRecompile(?string $locale = null): void
	{
		$this->translatorProvider->forceRecompile($locale ?? $this->getLocale());
	}

	private function parameter(mixed $value): string|int|float
	{
		if (is_string($value) || is_int($value) || is_float($value)) {
			return $value;
		}

		if ($value === null || is_bool($value) || $value instanceof Stringable) {
			return (string) $value;
		}
		throw new InvalidArgumentException('Translation parameters must be scalar, null or Stringable.');
	}
}
