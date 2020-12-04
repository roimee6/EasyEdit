<?php

namespace platz1de\EasyEdit\pattern;

use UnexpectedValueException;

class ParseError extends UnexpectedValueException
{
	public function __construct(string $message, int $pos = 0)
	{
		parent::__construct("Parse Error: " . $message . " at Character " . $pos);
	}
}