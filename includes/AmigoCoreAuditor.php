<?php

declare(strict_types=1);

namespace WPAmigoManage\Includes;

use WPAmigoManage\Providers\WPVulnerabilityProvider;
use WPAmigoManage\Helpers\VulnerabilityFormatter;

if (!defined('ABSPATH')) {
  exit;
}

final class AmigoCoreAuditor extends AmigoBaseAuditor
{

  public function audit(WPVulnerabilityProvider $provider): array
  {

    global $wp_version;

    $transient = get_site_transient('update_core');

    $current_version = $wp_version ?? get_bloginfo('version');

    $latest_version = $this->get_latest_version($transient, $current_version);

    $outcome = $this->safe_lookup(fn() => $provider->core($current_version));

    $vulnerabilities = VulnerabilityFormatter::format($outcome['vulnerabilities']);


    $audit = [
      'current_version' => $current_version,
      'latest_version' => $latest_version,
      'maybe_latest' => version_compare($current_version, $latest_version, '>='),
      'maybe_patch' => VulnerabilityFormatter::has_patch($vulnerabilities),
      'vulnerabilities' => $vulnerabilities,
      'total_vulnerabilities' => count($vulnerabilities),
      'error' => $outcome['error'],
    ];

    return [
      'audit' => $audit,
      'total_vulnerabilities' =>  count($vulnerabilities),
    ];
  }

  protected function get_latest_version(?\stdClass $transient, string $current_version,  string $key = ''): string
  {

    if (!$transient instanceof \stdClass || empty($transient->updates)) {
      return $current_version;
    }

    $latest = current(array_filter(
      $transient->updates,
      fn($update) => ($update->response ?? null) === 'latest'
    ));

    return $latest !== false ? $latest->current : $current_version;
  }
}
