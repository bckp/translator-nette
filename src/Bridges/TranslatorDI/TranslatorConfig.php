<?php

namespace Bckp\Translator\Nette\Bridges\TranslatorDI;

use Bckp\Translator\Nette\Resolvers\Resolver;

final readonly class TranslatorConfig
{
	public function __construct(
		/**
		 * @var string[]
		 */
		public array $languages,

		/**
		 * @var Resolver[]
		 */
		public array $resolvers,

		/**
		 * @var string[]
		 */
		public array $path,
		public bool $debugger,
		public bool $injectLatte,
	) {
	}
}
