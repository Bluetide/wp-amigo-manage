<?php

declare(strict_types=1);

namespace WPAmigoManage\Http;

enum HttpStatus: int
{
  case BadRequest           = 400;
  case Unauthorized         = 401;
  case Forbidden            = 403;
  case NotFound             = 404;
  case MethodNotAllowed     = 405;
  case RequestTimeout       = 408;
  case Conflict             = 409;
  case UnprocessableEntity  = 422;
  case TooManyRequest       = 429;
  case InternalServerError  = 500;
  case BadGateway           = 502;
  case GatewayTimeout       = 504;
}