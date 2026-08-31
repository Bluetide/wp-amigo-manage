<?php

namespace WPAmigoManage\Exceptions;

use WPAmigoManage\Http\HttpStatus;

final class NotFoundException extends WpAmigoException
{
    public function __construct(string $reason, string $message = 'Recurso no encontrado', array $details = [])
    {
        parent::__construct($message, HttpStatus::NotFound, $reason, $details);
    }
}
