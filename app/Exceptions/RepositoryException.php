<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class RepositoryException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(string $message, private readonly array $context = [], ?Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }

    /**
     * Extra context included automatically in the log entry when this exception is reported.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
