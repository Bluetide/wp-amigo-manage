<?php

namespace WPAmigoManage\Exceptions;

use WPAmigoManage\Http\HttpStatus;

final class GatewayTimeoutException extends WpAmigoException
{
    public function __construct(string $reason, string $message = 'Fallo en servicio externo', array $details = [])
    {
        parent::__construct($message, HttpStatus::BadGateway, $reason, $details);
    }
}