<?php

namespace WPAmigoManage\Exceptions;

use WPAmigoManage\Http\HttpStatus;

final class RequestTimeoutException extends WpAmigoException
{
    public function __construct(string $reason, string $message = 'Tiempo de espera agotado', array $details = [])
    {
        parent::__construct($message, HttpStatus::NotFound, $reason, $details);
    }
}
