<?php

declare(strict_types=1);

namespace WPAmigoManage\Helpers;

final class VersionMatcher
{
  public static function filter(string $version, ?array $vulnerabilities): array
  {
    if (empty($vulnerabilities)) return [];

    $filtered = array_filter(
      $vulnerabilities,
      fn(array $v): bool => self::affects($version, $v['operator'] ?? [])
    );

    return array_values($filtered);
  }

  private static function affects(string $version, array $operator): bool
  {
    $satisfies_min = self::satisfies_bound(
      $version,
      $operator['min_version'] ?? null,
      $operator['min_operator'] ?? null,
    );

    $satisfies_max = self::satisfies_bound(
      $version,
      $operator['max_version'] ?? null,
      $operator['max_operator'] ?? null,
    );

    return $satisfies_min && $satisfies_max;
  }

  private static function satisfies_bound(string $version, ?string $bound, ?string $operator): bool
  {

    // sin límite en ese extremo — no descarta nada
    if ($bound === null || $operator === null) return true;

    return version_compare($version, $bound, $operator);
  }
}
