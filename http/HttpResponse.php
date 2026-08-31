<?php

declare(strict_types=1);

namespace WPAmigoManage\Http;

final class HttpResponse
{

  public function __construct(
    public readonly int   $status,
    public readonly string $raw,
    public readonly array $headers = [],
  ) {}

  public function ok(): bool
  {
    return $this->status >= 200 && $this->status < 300;
  }

  public function nok(): bool
  {
    return $this->status >= 400;
  }

  /**
   * @throws \JsonException si el body no es JSON válido
   */
  public function json(): array
  {
    return json_decode($this->raw, true, 512, JSON_THROW_ON_ERROR);
  }
}
