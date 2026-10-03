<?php

declare(strict_types=1);

namespace Bckp\Translator\Nette\Interfaces;

use Bckp\Translator\CatalogueBuilder;

interface CatalogueFactory
{
	public function create(): CatalogueBuilder;
}
