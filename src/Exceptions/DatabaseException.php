<?php

declare(strict_types = 1);

namespace Alpipego\AWP\Database\Exceptions;

use Exception;

class DatabaseException extends Exception
{
	public function __construct(string $message)
	{
		parent::__construct($message);
	}
}
