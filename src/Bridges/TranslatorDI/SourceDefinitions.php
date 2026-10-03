<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Bridges\TranslatorDI;

use Closure;
use InvalidArgumentException;
use Nette\DI\Definitions\Statement;
use stdClass;

final readonly class SourceDefinitions
{
	private stdClass $definitions;

	public function __construct(SourceDefinition ...$definitions)
	{
		$record = new stdClass();
		foreach ($definitions as $definition) {
			$record->{$definition->id} = $definition;
		}
		$this->definitions = $record;
	}

	public static function from(mixed $value): self
	{
		if (!is_array($value)) {
			throw new InvalidArgumentException('Sources must be a named map.');
		}
		$definitions = [];
		foreach ($value as $id => $factory) {
			if (!is_string($id) || $id === '' || (!is_string($factory) && !$factory instanceof Statement)) {
				throw new InvalidArgumentException('Each source needs a non-empty name and a DI factory or service reference.');
			}
			$definitions[] = new SourceDefinition($id, $factory);
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
