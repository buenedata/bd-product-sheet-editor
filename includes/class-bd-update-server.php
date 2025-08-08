<?php
/**
 * BD Update Server
 * Simple update server for GitHub-based WordPress plugins
 * 
 * @package BD_Product_Sheet_Editor
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class BD_Update_Server {
    
    private $github_username;
    private $github_repo;
    
    public function __construct($github_username, $github_repo) {
        $this->github_username = $github_username;
        $this->github_repo = $github_repo;
        
        // Add REST API endpoint
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }
    
    /**
     * Register REST API endpoints
     */
    public function register_endpoints() {
        register_rest_route('bd/v1', '/update-check/(?P<plugin>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_update_check'],
            'permission_callback' => '__return_true',
            'args' => [
                'plugin' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'version' => [
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
        
        register_rest_route('bd/v1', '/plugin-info/(?P<plugin>[a-zA-Z0-9-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'handle_plugin_info'],
            'permission_callback' => '__return_true',
            'args' => [
                'plugin' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }
    
    /**
     * Handle update check requests
     */
    public function handle_update_check($request) {
        $plugin_slug = $request->get_param('plugin');
        $current_version = $request->get_param('version');
        
        // Get latest release from GitHub
        $release_data = $this->get_github_release();
        
        if (!$release_data) {
            return new WP_Error('no_release', 'No release found', ['status' => 404]);
        }
        
        $latest_version = ltrim($release_data['tag_name'], 'v');
        
        // Check if update is available
        $update_available = version_compare($current_version, $latest_version, '<');
        
        $response = [
            'plugin' => $plugin_slug,
            'current_version' => $current_version,
            'latest_version' => $latest_version,
            'update_available' => $update_available,
            'download_url' => $update_available ? $this->get_download_url($latest_version) : null,
            'release_notes' => $release_data['body'] ?? '',
            'published_at' => $release_data['published_at'] ?? '',
        ];
        
        return rest_ensure_response($response);
    }
    
    /**
     * Handle plugin info requests
     */
    public function handle_plugin_info($request) {
        $plugin_slug = $request->get_param('plugin');
        
        // Get latest release from GitHub
        $release_data = $this->get_github_release();
        
        if (!$release_data) {
            return new WP_Error('no_release', 'No release found', ['status' => 404]);
        }
        
        $response = [
            'name' => 'BD ' . ucwords(str_replace('-', ' ', $plugin_slug)),
            'slug' => $plugin_slug,
            'version' => ltrim($release_data['tag_name'], 'v'),
            'author' => 'Buene Data',
            'homepage' => "https://github.com/{$this->github_username}/{$this->github_repo}",
            'description' => $this->extract_description($release_data['body'] ?? ''),
            'download_link' => $this->get_download_url(ltrim($release_data['tag_name'], 'v')),
            'requires' => '5.0',
            'tested' => '6.4',
            'requires_php' => '7.4',
            'last_updated' => $release_data['published_at'] ?? date('Y-m-d'),
            'sections' => [
                'description' => $this->extract_description($release_data['body'] ?? ''),
                'changelog' => $release_data['body'] ?? 'No changelog available.',
            ],
        ];
        
        return rest_ensure_response($response);
    }
    
    /**
     * Get latest release from GitHub
     */
    private function get_github_release() {
        $cache_key = 'bd_github_release_' . $this->github_repo;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $url = "https://api.github.com/repos/{$this->github_username}/{$this->github_repo}/releases/latest";
        
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'BD-Update-Server/1.0',
            ],
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data || isset($data['message'])) {
            return false;
        }
        
        // Cache for 1 hour
        set_transient($cache_key, $data, HOUR_IN_SECONDS);
        
        return $data;
    }
    
    /**
     * Get download URL for specific version
     */
    private function get_download_url($version) {
        return "https://github.com/{$this->github_username}/{$this->github_repo}/releases/download/v{$version}/{$this->github_repo}.zip";
    }
    
    /**
     * Extract description from release notes
     */
    private function extract_description($release_notes) {
        // Extract first paragraph as description
        $lines = explode("\n", $release_notes);
        $description = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && !str_starts_with($line, '#') && !str_starts_with($line, '*') && !str_starts_with($line, '-')) {
                $description = $line;
                break;
            }
        }
        
        return $description ?: 'BD Plugin fra Buene Data';
    }
    
    /**
     * Clear update cache
     */
    public function clear_cache() {
        $cache_key = 'bd_github_release_' . $this->github_repo;
        delete_transient($cache_key);
    }
}

// Initialize update server if this is the main plugin
if (defined('BD_PSE_VERSION')) {
    new BD_Update_Server('buenedata', 'bd-product-sheet-editor');
}