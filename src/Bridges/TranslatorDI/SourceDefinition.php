<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Bridges\TranslatorDI;

use Nette\DI\Definitions\Statement;

final readonly class SourceDefinition
{
	public function __construct(public string $id, public string|Statement $factory)
	{
	}
}
