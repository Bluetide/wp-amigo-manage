<?php
namespace WPAmigoManage\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class Settings
{

    private const PAGE_SLUG = 'wp-amigo-manage';

    private const OPTION_GROUP = 'wp_amigo_options_group';

    /**
     * Register hooks for admin menu and settings.
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add settings page under the WordPress Settings menu.
     */
    public function add_settings_page(): void
    {
        add_options_page(
            __('WP Amigo Manage', 'wp-amigo-manage'),
            __('WP Amigo', 'wp-amigo-manage'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register the plugin settings pulling closures directly from the render map.
     * Note: The Webhook URL is currently handled as a constant and not exposed in the UI.
     */
    public function register_settings(): void
    {
        $renderers = $this->get_render_map();

        register_setting(
            self::OPTION_GROUP,
            'wp_amigo_notification_email',
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
                'default' => get_option('admin_email'),
            ]
        );

        add_settings_section(
            'wp_amigo_main_section',
            __('Configuración de Notificaciones', 'wp-amigo-manage'),
            $renderers['sections']['main'],
            self::PAGE_SLUG
        );

        add_settings_field(
            'wp_amigo_notification_email',
            __('Email de Notificación', 'wp-amigo-manage'),
            $renderers['fields']['wp_amigo_notification_email'],
            self::PAGE_SLUG,
            'wp_amigo_main_section'
        );
    }

    /**
     * Map holding all HTML rendering closures for sections and fields.
     */
    private function get_render_map(): array
    {
        return [
            'sections' => [
                'main' => function () {
                    echo '<p>' . esc_html__('Define los destinos a los cuales se enviará la información recolectada.', 'wp-amigo-manage') . '</p>';
                },
            ],
            'fields' => [
                'wp_amigo_notification_email' => function () {
                    $email = get_option('wp_amigo_notification_email', get_option('admin_email'));
                    printf(
                        '<input type="email" id="wp_amigo_notification_email" name="wp_amigo_notification_email" value="%s" class="regular-text" required />',
                        esc_attr($email)
                    );
                    echo '<p class="description">' . esc_html__('Dirección de correo válida donde se recibirán los avisos de vulnerabilidades.', 'wp-amigo-manage') . '</p>';
                },
            ],
        ];
    }

    /**
     * Render the settings page HTML.
     */
    public function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections(self::PAGE_SLUG);
                submit_button(__('Guardar Configuración', 'wp-amigo-manage'));
                ?>
            </form>
        </div>
        <?php
    }
}