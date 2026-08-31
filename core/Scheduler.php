<?php

declare(strict_types=1);

namespace WPAmigoManage\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class Scheduler
{
    private const CRON_HOOK = 'wp_amigo_manage_biweekly_audit';

    private const SCHEDULE_INTERVAL = 'fifteen_days';

    public function register(): void
    {
        add_filter('cron_schedules', [$this, 'add_fifteen_days_schedule']);
        add_action(self::CRON_HOOK, [$this, 'handle_cron_event']);

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), self::SCHEDULE_INTERVAL, self::CRON_HOOK);
        }
    }

    /**
     * Agrega 15 days como intervalo personalizado a WordPress Cron schedules.
     *
     * @param array $schedules Existing schedules.
     * @return array Modified schedules.
     */
    public function add_fifteen_days_schedule(array $schedules): array
    {
        $schedules[self::SCHEDULE_INTERVAL] = [
            'interval' => 15 * DAY_IN_SECONDS,
            'display'  => __('Cada 15 días', 'wp-amigo-manage'),
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
        $timestamp = wp_next_scheduled(self::CRON_HOOK);

        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }
}
