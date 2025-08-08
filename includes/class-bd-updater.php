<?php
/**
 * BD Plugin Updater
 * Håndterer automatisk oppdatering via GitHub
 * 
 * @package BD_Product_Sheet_Editor
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BD_Plugin_Updater {
    private $plugin_file;
    private $github_username;
    private $github_repo;
    private $version;
    private $plugin_slug;
    private $plugin_basename;
    private $plugin_data;

    public function __construct($plugin_file, $github_username, $github_repo) {
        $this->plugin_file = $plugin_file;
        $this->github_username = $github_username;
        $this->github_repo = $github_repo;
        $this->plugin_basename = plugin_basename($plugin_file);
        $this->plugin_slug = dirname($this->plugin_basename);
        
        // Get version from plugin header
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $this->plugin_data = get_plugin_data($plugin_file);
        $this->version = $this->plugin_data['Version'];

        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_pre_download', [$this, 'download_package'], 10, 3);
        
        // Add settings link to plugin page
        add_filter('plugin_action_links_' . $this->plugin_basename, [$this, 'add_action_links']);
        
        // Add update notice
        add_action('admin_notices', [$this, 'update_notice']);
    }

    /**
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        // Get remote version
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version, '<')) {
            $transient->response[$this->plugin_basename] = (object) [
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_basename,
                'new_version' => $remote_version,
                'url' => "https://github.com/{$this->github_username}/{$this->github_repo}",
                'package' => $this->get_download_url($remote_version),
                'tested' => '6.4',
                'requires_php' => '7.4',
                'compatibility' => new stdClass(),
                'icons' => [
                    '1x' => 'https://buenedata.no/wp-content/uploads/2023/11/logo-buene-data-dark.svg',
                    '2x' => 'https://buenedata.no/wp-content/uploads/2023/11/logo-buene-data-dark.svg',
                ],
            ];
        }

        return $transient;
    }

    /**
     * Get remote version from GitHub
     */
    private function get_remote_version() {
        // Check cache first
        $cache_key = 'bd_remote_version_' . $this->plugin_slug;
        $cached_version = get_transient($cache_key);
        
        if ($cached_version !== false) {
            return $cached_version;
        }

        $request = wp_remote_get(
            "https://api.github.com/repos/{$this->github_username}/{$this->github_repo}/releases/latest",
            [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'BD-Plugin-Updater/1.0',
                ]
            ]
        );
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $data = json_decode($body, true);
            
            if (isset($data['tag_name'])) {
                $version = ltrim($data['tag_name'], 'v');
                // Cache for 12 hours
                set_transient($cache_key, $version, 12 * HOUR_IN_SECONDS);
                return $version;
            }
        }

        return $this->version;
    }

    /**
     * Get download URL for specific version
     */
    private function get_download_url($version) {
        return "https://github.com/{$this->github_username}/{$this->github_repo}/releases/download/v{$version}/{$this->github_repo}.zip";
    }

    /**
     * Provide plugin information for update screen
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $request = wp_remote_get(
            "https://api.github.com/repos/{$this->github_username}/{$this->github_repo}/releases/latest",
            [
                'timeout' => 10,
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'BD-Plugin-Updater/1.0',
                ]
            ]
        );
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $data = json_decode($body, true);
            
            $changelog = $this->format_changelog($data['body'] ?? '');
            
            $result = (object) [
                'name' => $this->plugin_data['Name'],
                'slug' => $this->plugin_slug,
                'version' => ltrim($data['tag_name'] ?? $this->version, 'v'),
                'author' => '<a href="https://buenedata.no">Buene Data</a>',
                'homepage' => "https://github.com/{$this->github_username}/{$this->github_repo}",
                'short_description' => $this->plugin_data['Description'],
                'sections' => [
                    'description' => $this->plugin_data['Description'],
                    'changelog' => $changelog,
                    'installation' => 'Last ned og installer via WordPress admin eller last opp manuelt.',
                ],
                'download_link' => $this->get_download_url(ltrim($data['tag_name'] ?? $this->version, 'v')),
                'requires' => '5.0',
                'tested' => '6.4',
                'requires_php' => '7.4',
                'last_updated' => $data['published_at'] ?? date('Y-m-d'),
                'banners' => [
                    'low' => 'https://buenedata.no/wp-content/uploads/2023/11/bd-banner-low.jpg',
                    'high' => 'https://buenedata.no/wp-content/uploads/2023/11/bd-banner-high.jpg',
                ],
                'icons' => [
                    '1x' => 'https://buenedata.no/wp-content/uploads/2023/11/logo-buene-data-dark.svg',
                    '2x' => 'https://buenedata.no/wp-content/uploads/2023/11/logo-buene-data-dark.svg',
                ],
            ];
        }

        return $result;
    }

    /**
     * Format changelog from GitHub release notes
     */
    private function format_changelog($changelog) {
        if (empty($changelog)) {
            return 'Ingen endringer dokumentert.';
        }

        // Convert markdown to HTML
        $changelog = wp_kses_post($changelog);
        $changelog = wpautop($changelog);
        
        return $changelog;
    }

    /**
     * Download package from GitHub
     */
    public function download_package($result, $package, $upgrader) {
        if (strpos($package, "github.com/{$this->github_username}/{$this->github_repo}") === false) {
            return $result;
        }

        // Add custom headers for GitHub download
        add_filter('http_request_args', [$this, 'add_download_headers'], 10, 2);
        
        return $result;
    }

    /**
     * Add custom headers for GitHub downloads
     */
    public function add_download_headers($args, $url) {
        if (strpos($url, 'github.com') !== false) {
            $args['headers']['User-Agent'] = 'BD-Plugin-Updater/1.0';
            $args['timeout'] = 300; // 5 minutes for large files
        }
        return $args;
    }

    /**
     * Add action links to plugin page
     */
    public function add_action_links($links) {
        $settings_link = sprintf(
            '<a href="%s" style="color: #667eea; font-weight: 600;">⚙️ Innstillinger</a>',
            admin_url('admin.php?page=bd-product-sheet-editor')
        );
        
        $github_link = sprintf(
            '<a href="https://github.com/%s/%s" target="_blank" style="color: #10b981;">📚 GitHub</a>',
            $this->github_username,
            $this->github_repo
        );
        
        array_unshift($links, $settings_link);
        array_push($links, $github_link);
        
        return $links;
    }

    /**
     * Show update notice
     */
    public function update_notice() {
        if (!current_user_can('update_plugins')) {
            return;
        }

        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version, '<')) {
            $update_url = wp_nonce_url(
                self_admin_url('update.php?action=upgrade-plugin&plugin=' . $this->plugin_basename),
                'upgrade-plugin_' . $this->plugin_basename
            );
            
            echo '<div class="notice notice-info is-dismissible" style="border-left: 4px solid #667eea;">';
            echo '<p><strong>🚀 BD Product Sheet Editor:</strong> ';
            echo sprintf(
                'Ny versjon %s er tilgjengelig! <a href="%s" class="button button-primary" style="margin-left: 10px;">Oppdater nå</a>',
                $remote_version,
                $update_url
            );
            echo '</p></div>';
        }
    }

    /**
     * Clear update cache
     */
    public function clear_cache() {
        $cache_key = 'bd_remote_version_' . $this->plugin_slug;
        delete_transient($cache_key);
    }
}