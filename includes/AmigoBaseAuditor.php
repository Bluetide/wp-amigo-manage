<?php

declare(strict_types=1);

namespace WPAmigoManage\Includes;

use WPAmigoManage\Providers\WPVulnerabilityProvider;
use WPAmigoManage\Exceptions\WpAmigoException;

if (!defined('ABSPATH')) {
  exit;
}

abstract class AmigoBaseAuditor
{
  abstract public function audit(WPVulnerabilityProvider $provider): array;

  abstract protected function get_latest_version(?\stdClass $transient, string $current_version, string $key,): string;

  protected function safe_lookup(\Closure $lookup): array
  {
    try {
      return [
        'vulnerabilities' => $lookup(),
        'error' => null
      ];
    } catch (WpAmigoException $e) {
      return [
        'vulnerabilities' => [],
        'error' => $e->getReason()
      ];
    }
  }

  protected function extract_slug(string $file): string
  {
    $slug = dirname($file);
    return ('.' === $slug) ? basename($file, '.php') : $slug;
  }

  protected function resolve_transient(?\stdClass $transient, string $key, string $current_version): string
  {
    if (!$transient instanceof \stdClass) {
      return $current_version;
    }

    return $transient->response[$key]->new_version
      ?? $transient->no_update[$key]->new_version
      ?? $current_version;
  }
}
