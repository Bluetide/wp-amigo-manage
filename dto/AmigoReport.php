<?php

declare(strict_types=1);

namespace WPAmigoManage\Dto;

if (! defined('ABSPATH')) {
    exit;
}

class AmigoReport
{


    private array $core;

    private array $plugins;

    private array $themes;

    public function __construct(array $core, array $plugins, array $themes)
    {
        $this->core    = $core;
        $this->plugins = $plugins;
        $this->themes  = $themes;
    }

    /**
     * Serializa la información de la auditoria en un array asociativo.
     *
     * @return array Reporte de auditoria del sitio.
     */
    public function to_array(): array
    {
        return [
            'website'     => get_site_url(),
            'send_to'     => get_option('wp_amigo_notification_email', get_option('admin_email')),
            'summary'     => $this->summary(),
            'details'        => [
                'core'        => $this->core,
                'plugins'     => $this->plugins,
                'themes'      => $this->themes,
            ],
            'verified_at' => current_time('mysql', true),
        ];
    }


    /**
     * Genera un resumen de la información obtenida de los datos de aditoría.
     *
     * @return array Resumen de metricas.
     */
    private function summary(): array
    {
        $total_vulnerabilities = $this->core['total_vulnerabilities'] + $this->themes['total_vulnerabilities'] + $this->plugins['total_vulnerabilities'];

        $total_components_to_update = 0;

        if (isset($this->core['audit']['maybe_latest']) && !$this->core['audit']['maybe_latest']) {
            $total_components_to_update++;
        }

        foreach ($this->plugins as $plugin):

            if (isset($plugin['audit']['maybe_latest']) && !$plugin['audit']['maybe_latest']) {
                $total_components_to_update++;
            }

        endforeach;

        foreach ($this->themes as $theme):

            if (isset($theme['audit']['maybe_latest']) && !$theme['audit']['maybe_latest']) {
                $total_components_to_update++;
            }

        endforeach;

        return [
            'has_vulnerabilities'   => $total_vulnerabilities > 0,
            'total_vulnerabilities' => $total_vulnerabilities,
            'total_components_to_update'   => $total_components_to_update,
        ];
    }

    public function json(): string
    {
        return wp_json_encode($this->to_array());
    }
}
