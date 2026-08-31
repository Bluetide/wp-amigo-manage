<?php

declare(strict_types=1);

namespace WPAmigoManage\Exceptions;

use WPAmigoManage\Http\HttpStatus;

abstract class WpAmigoException extends \Exception
{
  public function __construct(
    string $message,
    private readonly HttpStatus $status,
    private readonly string     $reason,
    private readonly array      $details = [],
  ) {
    parent::__construct($message);
  }

  public function getReason(): string
  {
    return $this->reason;
  }

  public function getStatus(): int
  {
    return $this->status->value;
  }

  public function getDetails(): array
  {
    return $this->details;
  }

  public function print(): array
  {
    return [
      'status'  => $this->status,
      'reason'  => $this->reason,
      'message' => $this->getMessage(),
      'details' => $this->details,
    ];
  }
}
