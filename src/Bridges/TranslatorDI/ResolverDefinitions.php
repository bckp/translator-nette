<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Bridges\TranslatorDI;

use Closure;
use InvalidArgumentException;
use Nette\DI\Definitions\Statement;
use stdClass;

final readonly class ResolverDefinitions
{
	private stdClass $definitions;

	public function __construct(string|Statement ...$definitions)
	{
		$record = new stdClass();
		foreach ($definitions as $index => $definition) {
			$record->{(string) $index} = $definition;
		}
		$this->definitions = $record;
	}

	public static function from(mixed $value): self
	{
		if (!is_array($value)) {
			throw new InvalidArgumentException('Resolvers must be a list.');
		}
		$definitions = [];
		foreach ($value as $definition) {
			if (!is_string($definition) && !$definition instanceof Statement) {
				throw new InvalidArgumentException('Resolvers must be DI factories or service references.');
			}
			$definitions[] = $definition;
		}

		return new self(...$definitions);
	}

	public function each(Closure $consumer): void
	{
		foreach (get_object_vars($this->definitions) as $definition) {
			$consumer($definition);
		}
	}
}
