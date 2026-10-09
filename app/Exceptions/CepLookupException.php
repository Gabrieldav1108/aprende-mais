<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * The CEP lookup service could not be reached or answered with an unexpected error.
 */
class CepLookupException extends RuntimeException
{
    public function __construct(string $message, private readonly string $cep, ?Throwable $previous = null)
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
        return ['cep' => $this->cep];
    }
}
