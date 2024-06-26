<?php

declare(strict_types=1);

namespace Inpsyde\UsersTable;

final class UsersTable
{
    /**
     * Holds the singleton instance of this class
     * @var UsersTable|null
     */
    private static ?UsersTable $instance = null;
    public static string $pluginDir;
    public static string $pluginUrl;

    /**
     * Private constructor to prevent direct creation
     */
    private function __construct()
    {
        self::$pluginDir = plugin_dir_path(dirname(__FILE__, 1)); // Go up one level from this file.
        self::$pluginUrl = plugin_dir_url(dirname(__FILE__, 1)); // Go up one level from this file.
        $this->initHooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function initHooks(): void
    {
        add_action('init', [$this, 'registerRewriteRule']);
        add_action('init', [$this, 'loadTextDomain']);
        add_filter('query_vars', [$this, 'addQueryVar']);
        add_action('template_redirect', [$this, 'handleTemplateRedirect']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScriptsAndStyles']);
        add_action('wp_ajax_fetch_user_details', [$this, 'handleAjaxFetchUserDetails']);
        add_action('wp_ajax_nopriv_fetch_user_details', [$this, 'handleAjaxFetchUserDetails']);

        register_activation_hook(__FILE__, [$this, 'flushRewriteRulesOnActivation']);
        register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
    }

    /**
     * Get the plugin directory path
     */
    public static function pluginDirPath(): string
    {
        return self::$pluginDir;
    }

    /**
     * Get the plugin directory URL
     */
    public static function pluginDirUrl(): string
    {
        return self::$pluginUrl;
    }

    /**
     * Load text domain
     */
    public function loadTextDomain()
    {
        load_plugin_textdomain('users-table', false, basename(dirname(__FILE__)) . '/languages');
    }

    /**
     * Registers a rewrite rule that maps custom endpoint to a query variable
     */
    public function registerRewriteRule(): void
    {
        add_rewrite_rule('^users-table/?$', 'index.php?users_table=1', 'top');
    }

    /**
     * Adds custom query var
     *
     * @param array $vars
     * @return array
     */
    public function addQueryVar(array $vars): array
    {
        $vars[] = 'users_table';
        return $vars;
    }

    /**
     * Handles the template redirect for the custom endpoint
     */
    public function handleTemplateRedirect(): void
    {
        if ($this->isOurEndpoint()) {
            $users = $this->fetchUsersFromApi();
            $this->displayUsersTable($users);
            die();
        }
    }

    /**
     * Check if custom endpoint
     */
    private function isOurEndpoint(): bool
    {
        return intval(get_query_var('users_table', 0)) === 1;
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueueScriptsAndStyles(): void
    {
        if ($this->isOurEndpoint()) {
            wp_enqueue_style(
                'users-table-style',
                self::pluginDirUrl() . 'dist/style.css',
                [],
                filemtime(self::pluginDirPath() . 'dist/style.css'),
                'all'
            );
            wp_enqueue_script(
                'users-table-script',
                self::pluginDirUrl() . 'dist/bundle.js',
                ['jquery'],
                filemtime(self::pluginDirPath() . 'dist/bundle.js'),
                true
            );

            $scriptParams = [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('fetch_user_details'),
            ];
            wp_localize_script('users-table-script', 'myUsersTable', $scriptParams);
        }
    }

    /**
     * Handle the AJAX request to fetch user details
     */
    public function handleAjaxFetchUserDetails(): void
    {
        check_ajax_referer('fetch_user_details', 'nonce');

        if (isset($_GET['user_id'])) {
            $userId = intval($_GET['user_id']);
            $users = $this->fetchUsersFromApi();

            $userDetails = array_filter($users, static function (array $user) use ($userId): bool {
                return $user['id'] === $userId;
            });

            if (!empty($userDetails)) {
                wp_send_json_success(reset($userDetails));
            }
        }
    }

    /**
     * Flushes rewrite rules on plugin activation
     */
    public function flushRewriteRulesOnActivation(): void
    {
        $this->registerRewriteRule();
        flush_rewrite_rules();
    }

    /**
     * Specifically for testing hook initialisation
     */
    public function testInit()
    {
        $this->initHooks(); // Directly call the method that adds hooks
    }

    /**
     * Fetches users from the API
     *
     * @return array
     */
    public function fetchUsersFromApi(): array
    {

        $transientKey = 'users_table_api_data';
        $cachedData = get_transient($transientKey);

        if ($cachedData !== false) {
            return $cachedData;
        }

        $response = wp_remote_get('https://jsonplaceholder.typicode.com/users');
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return []; // Make sure to return an empty array on error
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (!is_array($data) || is_null($data)) {
            return []; // Ensure we return an empty array if $data is not valid
        }

        set_transient($transientKey, $data, HOUR_IN_SECONDS);

        return $data;
    }

    /**
     * Displays the users table
     *
     * @param array $users
     */
    private function displayUsersTable(array $users): void
    {
        $templatePath = self::pluginDirPath() . 'templates/table.php';

        if (file_exists($templatePath)) {
            include $templatePath;
        }
    }

    /**
     * Returns the singleton instance of this class
     *
     * @return UsersTable
     */
    public static function instance(): UsersTable
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
