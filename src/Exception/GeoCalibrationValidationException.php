<?php

namespace App\Exception;

final class GeoCalibrationValidationException extends \InvalidArgumentException
{
    /** @param list<string> $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }

    /** @return list<string> */
    public function getErrors(): array { return $this->errors; }
}
