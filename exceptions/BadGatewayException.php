<?php

namespace WPAmigoManage\Exceptions;

use WPAmigoManage\Http\HttpStatus;

final class BadGatewayException extends WpAmigoException
{
    public function __construct(string $reason, string $message = 'Fallo en servicio externo', array $details = [])
    {
        parent::__construct($message, HttpStatus::BadGateway, $reason, $details);
    }
}
