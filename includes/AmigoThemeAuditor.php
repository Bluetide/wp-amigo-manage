<?php

declare(strict_types=1);

namespace WPAmigoManage\Includes;

use WPAmigoManage\Providers\WPVulnerabilityProvider;
use WPAmigoManage\Helpers\VulnerabilityFormatter;

if (!defined('ABSPATH')) {
  exit;
}

final class AmigoThemeAuditor extends AmigoBaseAuditor
{

  public function audit(WPVulnerabilityProvider $provider): array
  {

    $transient = get_site_transient('update_themes');

    $current_theme = get_stylesheet();
    $current_themes = wp_get_themes();

    $audit = [];

    foreach ($current_themes as $slug => $theme):

      $slug = (string) $slug;

      $current_version = $theme->get('Version') ?: '';

      $latest_version = $this->get_latest_version($transient, $current_version, $slug);


      $outcome = $this->safe_lookup(fn() => $provider->theme($slug, $current_version));

      $vulnerabilities = VulnerabilityFormatter::format($outcome['vulnerabilities']);

      $audit[] = [
        'name' => $theme->get('Name') ?: '',
        'current_version' => $current_version,
        'latest_version' => $latest_version,
        'maybe_active' => $slug === $current_theme,
        'maybe_latest' => version_compare($current_version, $latest_version, '>='),
        'maybe_patch' => VulnerabilityFormatter::has_patch($vulnerabilities),
        'vulnerabilities' => $vulnerabilities,
        'error' => $outcome['error'],
      ];

    endforeach;

    return [
      'audit' => $audit,
      'total_vulnerabilities' => array_reduce($audit, fn($carry, $current) => $carry + count($current['vulnerabilities'] ?? []), 0),

    ];
  }

  protected function get_latest_version(?\stdClass $transient, string $current_version, string $slug): string
  {
     return $this->resolve_transient($transient, $slug, $current_version);
  }
}
