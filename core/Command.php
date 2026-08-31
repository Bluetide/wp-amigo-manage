<?php

declare(strict_types=1);

namespace WPAmigoManage\Core;

use WP_CLI;
use WPAmigoManage\Exceptions\WpAmigoException;

if (! defined('ABSPATH')) {
    exit;
}

final class Command
{

    public function register(): void
    {
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('amigo audit', [$this, 'run_audit']);
        }
    }

    /**
     * Ejecuta una auditoria manual del sitio via WP-CLI y guardar un JSON como log de la auditoria.
     * Webhook dispatch is completely bypassed here.
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function run_audit(array $args = [], array $assoc_args = []): void
    {

        $maybe_send = (bool) \WP_CLI\Utils\get_flag_value($assoc_args, 'send', false);

        WP_CLI::line('[WP_AMIGO_MANAGE] - Iniciando auditoría de vulnerabilidades...');

        try {
            $report = Plugin::instance()->generate_audit();
            $log_file = $this->write_log($report->json());
            WP_CLI::success('[WP_AMIGO_MANAGE] - Auditoría completada. JSON guardado en: ' . $log_file);
        } catch (WpAmigoException $e) {
            WP_CLI::error('[WP_AMIGO_MANAGE] - Error al ejecutar la auditoría: ' . $e->getMessage());
        }

        if ($maybe_send) {
            try {
                Plugin::instance()->dispatch_audit($report);
                WP_CLI::success('[WP_AMIGO_MANAGE] - Reporte enviado al webhook.');
            } catch (WpAmigoException $e) {
                WP_CLI::warning('[WP_AMIGO_MANAGE] - Reporte generado, pero no se pudo enviar al webhook: ' . $e->getMessage());
            }
        }
    }

    /**
     * Guarda la salida JSON como un archivo unico para evitar sobreescritura.
     *
     * @param string $json_data Raw JSON string from Site_Report.
     * @return string Path to the created log file.
     */
    private function write_log(string $json_data): string
    {
        $logs_dir = WP_AMIGO_MANAGE_DIR . 'logs/';

        if (! file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }

        $htaccess_file = $logs_dir . '.htaccess';
        if (! file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, 'Deny from all');
        }

        // Nombre único por ejecución (Fecha_Hora_IDUnico.json)
        $filename = sprintf('audit-wp-amigo-manage-%s-%s.json', current_time('Y-m-d_H-i-s'), uniqid());
        $filepath = $logs_dir . $filename;

        file_put_contents($filepath, $json_data);

        return $filepath;
    }
}
