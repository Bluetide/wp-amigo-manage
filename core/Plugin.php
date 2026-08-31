<?php

declare(strict_types=1);

namespace WPAmigoManage\Core;

use WPAmigoManage\Dto\AmigoReport;
use WPAmigoManage\Http\HttpFetcher;
use WPAmigoManage\Includes\AmigoCoreAuditor;
use WPAmigoManage\Includes\AmigoPluginAuditor;
use WPAmigoManage\Includes\AmigoThemeAuditor;
use WPAmigoManage\Providers\WPVulnerabilityProvider;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{

    private static ?Plugin $instance = null;
    private readonly HttpFetcher $fetcher;

    public static function instance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->fetcher = new HttpFetcher();
    }

    private function __clone() {}

    public function __wakeup(): void
    {
        throw new \LogicException('No se puede deserializar un singleton.');
    }

    public function run(): void
    {
        (new Settings())->register();

        (new Scheduler())->register();

        if (defined('WP_CLI') && WP_CLI) {
            (new Command())->register();
        }
    }

    /**
     * Genera un reporte de auditoria sin enviar la información al webhook.
     */
    public function generate_audit(): AmigoReport
    {
        $provider = new WPVulnerabilityProvider($this->fetcher);

        $core_auditor = new AmigoCoreAuditor();
        $plugin_auditor = new AmigoPluginAuditor();
        $theme_auditor = new AmigoThemeAuditor();

        $core_data = $core_auditor->audit($provider);
        $plugin_data = $plugin_auditor->audit($provider);
        $theme_data = $theme_auditor->audit($provider);

        $report = new AmigoReport($core_data, $plugin_data, $theme_data);

        return $report;
    }

    /**
     * Envia un reporte de auditoria al webhook.
     */
    public function dispatch_audit(AmigoReport $report): void
    {
        $dispatcher = new Dispatcher($this->fetcher);
        $dispatcher->send($report);
    }
}
