<?php

declare(strict_types=1);

namespace WPAmigoManage\Core;

final class Logger
{
  public static function error(string $context, string $message, array $extra = []): void
  {
    $encode = wp_json_encode(array_merge(
      ['message' => $message],
      $extra
    ));

    $output = sprintf(
      '[WP_AMIGO_MANAGE] [ERROR] [%s] %s',
      $context,
      $encode
    );

    error_log($output);
  }
}
