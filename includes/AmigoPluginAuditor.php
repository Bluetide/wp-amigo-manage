<?php

declare(strict_types=1);

namespace WPAmigoManage\Includes;

use WPAmigoManage\Providers\WPVulnerabilityProvider;
use WPAmigoManage\Helpers\VulnerabilityFormatter;

if (!defined('ABSPATH')) {
  exit;
}

final class AmigoPluginAuditor extends AmigoBaseAuditor
{

  public function audit(WPVulnerabilityProvider $provider): array
  {

    if (!function_exists('get_plugins')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $transient = get_site_transient('update_plugins');

    $current_plugins = get_plugins();

    $audit = [];

    foreach ($current_plugins as $file => $plugin):

      $file = (string) $file;

      $slug = $this->extract_slug($file);

      $current_version = $plugin['Version'] ?? '';

      $latest_version = $this->get_latest_version($transient, $current_version,  $file);

      $outcome = $this->safe_lookup(fn() => $provider->plugin($slug, $current_version));

      $vulnerabilities = VulnerabilityFormatter::format($outcome['vulnerabilities']);

      $audit[] = [
        'name' => $plugin['Name'] ?? '',
        'current_version' => $current_version,
        'latest_version' => $latest_version,
        'maybe_active' => is_plugin_active($file),
        'maybe_latest' => version_compare($current_version, $latest_version, '>='),
        'maybe_patch' => VulnerabilityFormatter::has_patch($vulnerabilities),
        'vulnerabilities' => $vulnerabilities,
        'error' => $outcome['error'],
      ];

    endforeach;


    return [
      'audit' => $audit,
      'total_vulnerabilities' => array_reduce($audit, fn($carry, $current) => $carry + count($current), 0),
    ];
  }

  protected function get_latest_version(?\stdClass $transient, string $current_version, string $file,): string
  {
    return $this->resolve_transient($transient, $file, $current_version);
  }
}
