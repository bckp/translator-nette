<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Bridges\TranslatorDI;

use Bckp\Translator\StringList;
use Nette\DI\Definitions\Statement;

final class TranslatorConfig
{
	public StringList $languages;

	public SourceDefinitions $sources;

	public ResolverDefinitions $resolvers;

	public float $checkProbability;

	public ?bool $debugMode;

	public ?bool $debugger;

	public bool $injectLatte;

	public string|Statement $pluralProvider;

	public string|Statement|null $storage;
}
