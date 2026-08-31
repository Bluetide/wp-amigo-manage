<?php

declare(strict_types=1);

namespace WPAmigoManage\Core;

use WPAmigoManage\Http\HttpFetcher;
use WPAmigoManage\Dto\AmigoReport;
use WPAmigoManage\Exceptions\WpAmigoException;
use WPAmigoManage\Exceptions\BadGatewayException;
use WPAmigoManage\Exceptions\RequestTimeoutException;

if (!defined('ABSPATH')) {
    exit;
}

final class Dispatcher
{
    public function __construct(private readonly HttpFetcher $fetcher) {}

    /**
     * Envía el reporte al webhook configurado.
     *
     * @throws WpAmigoException si la URL no está configurada o el envío falla.
     */
    public function send(AmigoReport $report): void
    {
        try {
            //code...

            $url = $this->get_webhook_url();

            if (empty($url)) {
                throw new BadGatewayException(
                    reason: 'DISPATCHER_WEBHOOK_URL_MISSING',
                    message: 'No hay una URL de webhook configurada para el reporte de auditoría',
                );
            }

            $response = $this->fetcher->post($url, $report->json(), [
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'User-Agent' => 'WPAmigoManage/' . (defined('WP_AMIGO_MANAGE_VERSION') ? WP_AMIGO_MANAGE_VERSION : '0.0.1'),
                ],
            ]);

            if ($response->nok()) {
                throw match (true) {
                    $response->status === 408 => new RequestTimeoutException(
                        reason: 'DISPATCHER_UPSTREAM_TIMEOUT',
                        message: 'El webhook tardó demasiado en responder',
                        details: ['status' => $response->status, 'body' => $response->raw],
                    ),
                    default => new BadGatewayException(
                        reason: 'DISPATCHER_UPSTREAM_ERROR',
                        message: 'El webhook respondió con error',
                        details: ['status' => $response->status, 'body' => $response->raw],
                    ),
                };
            }
        } catch (WpAmigoException $e) {
            Logger::error('WP-VULNERABILITY', $e->getMessage(), $e->print());
            throw $e;
        }
    }

    private function get_webhook_url(): string
    {
        if (defined('WP_AMIGO_MANAGE_WEBHOOK_URL') && !empty(WP_AMIGO_MANAGE_WEBHOOK_URL)) {
            return esc_url_raw(WP_AMIGO_MANAGE_WEBHOOK_URL);
        }

        return esc_url_raw(get_option('wp_amigo_webhook_url', ''));
    }
}
