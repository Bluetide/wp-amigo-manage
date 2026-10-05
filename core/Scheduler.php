<?php

declare(strict_types=1);

namespace WPAmigoManage\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class Scheduler
{
    private const CRON_HOOK = 'wp_amigo_manage_monthly_audit';
    private const LEGACY_CRON_HOOK = 'wp_amigo_manage_biweekly_audit';

    private const SCHEDULE_INTERVAL = 'monthly';

    public function register(): void
    {
        add_filter('cron_schedules', [$this, 'add_monthly_schedule']);
        add_action(self::CRON_HOOK, [$this, 'handle_cron_event']);

        // Limpia el evento anterior (quincenal) si todavía existe.
        wp_clear_scheduled_hook(self::LEGACY_CRON_HOOK);

        // Si el evento existe pero con otro intervalo, lo reprograma.
        $current = wp_get_schedule(self::CRON_HOOK);
        
        if ($current !== false && $current !== self::SCHEDULE_INTERVAL) {
            wp_clear_scheduled_hook(self::CRON_HOOK);
        }

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), self::SCHEDULE_INTERVAL, self::CRON_HOOK);
        }
    }

    /**
     * Agrega un intervalo mensual (30 días) a los schedules de WP-Cron.
     *
     * @param array $schedules Existing schedules.
     * @return array Modified schedules.
     */
    public function add_monthly_schedule(array $schedules): array
    {
        $schedules[self::SCHEDULE_INTERVAL] = [
            'interval' => 30 * DAY_IN_SECONDS,
            'display'  => __('Una vez al mes', 'wp-amigo-manage'),
        ];

        return $schedules;
    }

    public function handle_cron_event(): void
    {
        $report = Plugin::instance()->generate_audit();

        Plugin::instance()->dispatch_audit($report);
    }

    public static function clear_schedule(): void
    {
        wp_clear_scheduled_hook(self::CRON_HOOK);
        wp_clear_scheduled_hook(self::LEGACY_CRON_HOOK);
    }
}