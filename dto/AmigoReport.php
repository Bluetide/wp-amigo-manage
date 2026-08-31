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
        $total_vulnerabilities = 0;
        $total_components_to_update   = 0;

        if (!empty($this->core['vulnerabilities']) && is_array($this->core['vulnerabilities'])) {
            $total_vulnerabilities += count($this->core['vulnerabilities']);
        }

        if (isset($this->core['maybe_latest']) && !$this->core['maybe_latest']) {
            $total_components_to_update++;
        }

        foreach ($this->plugins as $plugin):

            if (!empty($plugin['vulnerabilities']) && is_array($plugin['vulnerabilities'])) {
                $total_vulnerabilities += count($plugin['vulnerabilities']);
            }

            if (isset($plugin['maybe_latest']) && !$plugin['maybe_latest']) {
                $total_components_to_update++;
            }

        endforeach;

        foreach ($this->themes as $theme):

            if (!empty($theme['vulnerabilities']) && is_array($theme['vulnerabilities'])) {
                $total_vulnerabilities += count($theme['vulnerabilities']);
            }

            if (isset($theme['maybe_latest']) && !$theme['maybe_latest']) {
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
