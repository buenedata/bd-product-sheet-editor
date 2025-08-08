<?php
/**
 * Buene Data Admin Menu Helper
 * Plasser denne filen i /includes/ i alle BD plugins.
 */

if (!function_exists('bd_add_buene_data_menu')) {
    function bd_add_buene_data_menu($submenu_name, $submenu_slug, $submenu_callback, $emoji = '🎨') {
        global $menu;
        
        // Sjekk om Buene Data hovedmeny allerede eksisterer
        $bd_menu_exists = false;
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (isset($menu_item[2]) && $menu_item[2] === 'buene-data') {
                    $bd_menu_exists = true;
                    break;
                }
            }
        }
        
        // Opprett hovedmeny kun hvis den ikke finnes fra før
        if (!$bd_menu_exists) {
            add_menu_page(
                __('Buene Data', 'buene-data'),
                __('Buene Data', 'buene-data'),
                'manage_options',
                'buene-data',
                function() {
                    if (function_exists('bd_buene_data_overview_page')) {
                        bd_buene_data_overview_page();
                    } else {
                        echo '<div class="wrap"><h1>Buene Data</h1><p>Oversiktsside ikke tilgjengelig.</p></div>';
                    }
                },
                'data:image/svg+xml;base64,' . base64_encode('<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10 2L3 7V18H7V14H13V18H17V7L10 2Z" fill="currentColor"/></svg>'),
                58.5
            );
        }
        
        // Legg alltid til denne pluginen som undermeny
        add_submenu_page(
            'buene-data',
            $emoji . ' ' . $submenu_name,
            $emoji . ' ' . $submenu_name,
            'manage_options',
            $submenu_slug,
            $submenu_callback
        );
    }
}

// Helper function to check if plugin has GitHub integration
if (!function_exists('bd_plugin_has_github_integration')) {
    function bd_plugin_has_github_integration($plugin_file) {
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
        $plugin_dir = dirname($plugin_path);
        
        // Check if plugin has GitHub updater classes or update URI
        $has_updater = file_exists($plugin_dir . '/includes/class-bd-updater.php');
        $has_update_server = file_exists($plugin_dir . '/includes/class-bd-update-server.php');
        
        // Check plugin header for Update URI
        $plugin_data = get_file_data($plugin_path, ['UpdateURI' => 'Update URI']);
        $has_update_uri = !empty($plugin_data['UpdateURI']) && strpos($plugin_data['UpdateURI'], 'github.com') !== false;
        
        return $has_updater || $has_update_server || $has_update_uri;
    }
}

// Denne callbacken lager oversiktssiden (bare én plugin trenger å ha denne!)
if (!function_exists('bd_buene_data_overview_page')) {
    function bd_buene_data_overview_page() {
        // Debug informasjon
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BD: bd_buene_data_overview_page called');
        }
        
        // Finn alle BD-plugins i plugins-mappen
        $plugins = get_plugins();
        $bd_plugins = [];
        
        if (empty($plugins)) {
            echo '<div class="wrap"><h1>Buene Data</h1><p>Kunne ikke laste plugin-informasjon.</p></div>';
            return;
        }
        
        foreach ($plugins as $plugin_file => $data) {
            if (
                (isset($data['Author']) && stripos($data['Author'], 'Buene Data') !== false) ||
                (isset($data['PluginURI']) && stripos($data['PluginURI'], 'buenedata') !== false) ||
                (isset($data['Name']) && (stripos($data['Name'], 'BD ') === 0)) ||
                (isset($data['TextDomain']) && stripos($data['TextDomain'], 'bd-') === 0)
            ) {
                $is_active = is_plugin_active($plugin_file);
                $bd_plugins[] = [
                    'Name'        => $data['Name'],
                    'Description' => $data['Description'],
                    'Version'     => $data['Version'],
                    'PluginURI'   => $data['PluginURI'],
                    'File'        => $plugin_file,
                    'Active'      => $is_active,
                    'Title'       => $data['Title'] ?? $data['Name'],
                    'Emoji'       => (
                        stripos($data['Name'], 'CleanDash') !== false ? '🧹' :
                        (stripos($data['Name'], 'Client Suite') !== false ? '🎨' : '🔧')
                    ),
                    'LastUpdated' => file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)
                        ? date("d.m.Y H:i", filemtime(WP_PLUGIN_DIR . '/' . $plugin_file))
                        : '',
                ];
            }
        }
        
        // Hvis ingen BD plugins ble funnet, vis en melding
        if (empty($bd_plugins)) {
            echo '<div class="wrap">';
            echo '<h1>Buene Data Plugin Suite</h1>';
            echo '<div class="notice notice-info"><p>Ingen Buene Data plugins ble funnet. Sjekk at plugin-filene har korrekt header-informasjon.</p></div>';
            echo '<p>For at plugins skal vises her må de ha:</p>';
            echo '<ul><li>Author: "Buene Data"</li><li>Plugin navn som starter med "BD "</li><li>Text Domain som starter med "bd-"</li><li>Plugin URI som inneholder "buenedata"</li></ul>';
            echo '</div>';
            return;
        }
        // BD Branding
        $bd_contact = '<div class="bd-contact" style="text-align:center; margin-top:40px;">
            <strong>Buene Data</strong> &nbsp;|&nbsp; <a href="https://buenedata.no" target="_blank">buenedata.no</a> &nbsp;|&nbsp; <a href="mailto:support@buenedata.no">support@buenedata.no</a>
        </div>';

        // Output oversiktsside
        echo '<div class="wrap bd-overview">';
        echo '<div class="bd-overview-header" style="margin-bottom:30px;">
                <div>
                    <h1 class="gradient-text" style="margin-bottom:12px;font-size:2.4em; font-weight:700;">Buene Data Plugin Suite</h1>
                    <p style="font-size:16px;color:#374151;">Profesjonelle WordPress-verktøy for moderne byrå og nettsteder</p>
                </div>
                <div><img src="https://buenedata.no/wp-content/uploads/2023/11/logo-buene-data-dark.svg" alt="Buene Data" height="56"></div>
            </div>';
        // GRID
        echo '<div class="bd-settings-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(340px,1fr));gap:32px;">';
        foreach ($bd_plugins as $plugin) {
            $card_gradient = $plugin['Emoji'] === '🧹'
                ? 'linear-gradient(135deg,#667eea 0%,#764ba2 100%)'
                : ($plugin['Emoji'] === '🎨'
                    ? 'linear-gradient(135deg,#5a67d8 0%,#6b46c1 100%)'
                    : 'linear-gradient(135deg,#f59e0b 0%,#d97706 100%)');

            echo '<div class="bd-plugin-card bd-hover-lift" style="background:#fff;border-radius:20px;box-shadow:0 8px 30px rgba(0,0,0,0.08);border:1px solid #e2e8f0;position:relative;overflow:hidden;padding:38px 30px;transition:all .3s;">
                    <div style="font-size:38px;margin-bottom:12px;">' . $plugin['Emoji'] . '</div>
                    <h2 class="gradient-text" style="font-size:1.4em;font-weight:700; margin-bottom:8px;">' . esc_html($plugin['Name']) . '</h2>
                    <p style="color:#64748b;font-size:14px; min-height:38px; margin-bottom:15px;">' . esc_html($plugin['Description']) . '</p>
                    <div style="margin-bottom:12px;">
                        <span class="bd-label" style="background:' . ($plugin['Active'] ? '#10b981' : '#e5e7eb') . ';color:' . ($plugin['Active'] ? 'white' : '#6b7280') . ';padding:3px 14px;border-radius:14px; font-size:12px;font-weight:600;letter-spacing:.5px;">' . ($plugin['Active'] ? 'Aktiv' : 'Ikke aktiv') . '</span>
                        <span style="color:#64748b;font-size:12px; margin-left:10px;">Versjon ' . esc_html($plugin['Version']) . '</span>
                    </div>
                    <div style="margin-bottom:14px;"><span style="color:#64748b;font-size:12px;">Sist oppdatert: ' . esc_html($plugin['LastUpdated']) . '</span></div>';
            if ($plugin['Active']) {
                // Prøv å finne riktig admin-side basert på plugin navn
                $admin_slug = '';
                if (stripos($plugin['Name'], 'CleanDash') !== false) {
                    $admin_slug = 'bd-cleandash';
                } elseif (stripos($plugin['Name'], 'Client Suite') !== false) {
                    $admin_slug = 'bd-client-suite';
                } elseif (stripos($plugin['Name'], 'Product Sheet Editor') !== false) {
                    $admin_slug = 'bd-product-sheet-editor';
                } else {
                    // Fallback til å bruke plugin-mappe navn
                    $admin_slug = explode('/', $plugin['File'])[0];
                }
                
                $url = admin_url('admin.php?page=' . $admin_slug);
                echo '<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px;">';
                echo '<a href="' . esc_url($url) . '" class="button button-primary" style="background:' . $card_gradient . ';color:white;border:none;border-radius:7px;padding:8px 24px;font-weight:600;font-size:14px;box-shadow:0 1px 3px rgba(14,165,233,0.12);transition:all .2s;">Åpne innstillinger</a>';
                
                // Add update check button for plugins with GitHub integration
                if (bd_plugin_has_github_integration($plugin['File'])) {
                    echo '<button type="button" class="button bd-check-updates-btn" data-plugin="' . esc_attr($plugin['File']) . '" style="background:#f8fafc;color:#64748b;border:1px solid #e2e8f0;border-radius:7px;padding:8px 16px;font-weight:600;font-size:14px;transition:all .2s;">🔍 Sjekk oppdatering</button>';
                }
                echo '</div>';
            } else {
                echo '<span class="button" style="background:#f1f5f9;color:#a0aec0;border:none;border-radius:7px;padding:8px 24px;font-weight:600;font-size:14px;">Ikke aktivert</span>';
            }
            if (!empty($plugin['PluginURI'])) {
                echo '<div style="margin-top:18px;"><a href="' . esc_url($plugin['PluginURI']) . '" target="_blank" style="font-size:13px;color:#0ea5e9;">Se dokumentasjon &rarr;</a></div>';
            }
            echo '</div>';
        }
        echo '</div>'; // grid
        echo $bd_contact;
        echo '</div>'; // wrap

        // Inline CSS – kan evt flyttes til egen .css-fil for større prosjekt
        ?>
        <style>
        .bd-overview { background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); padding: 32px 0; min-height: 100vh;}
        .gradient-text { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
        .bd-plugin-card:hover { transform: translateY(-8px); box-shadow: 0 15px 50px rgba(0,0,0,0.12);}
        @media (max-width: 800px) { .bd-settings-grid { grid-template-columns:1fr !important; } .bd-plugin-card { padding:24px 12px;}}
        .bd-label {display: inline-block; padding: 4px 10px; border-radius: 14px; font-size: 12px; font-weight: 500;}
        .button-primary:focus { outline: 2px solid #0ea5e9; }
        .bd-check-updates-btn:hover { background: #e2e8f0 !important; border-color: #cbd5e1 !important; }
        .bd-check-updates-btn.bd-loading { opacity: 0.7; cursor: not-allowed; }
        .bd-update-notice { margin-top: 12px; padding: 8px 12px; border-radius: 6px; font-size: 13px; }
        .bd-update-notice.success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .bd-update-notice.info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .bd-update-notice.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Handle update check button clicks
            $('.bd-check-updates-btn').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const pluginFile = $button.data('plugin');
                const $card = $button.closest('.bd-plugin-card');
                const originalText = $button.text();
                
                // Update button state
                $button.addClass('bd-loading').prop('disabled', true);
                $button.text('🔄 Sjekker...');
                
                // Remove any existing notices
                $card.find('.bd-update-notice').remove();
                
                // Send AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'bd_menu_check_updates',
                        plugin_file: pluginFile,
                        nonce: '<?php echo wp_create_nonce('bd_menu_updates'); ?>'
                    },
                    success: function(response) {
                        // Reset button state
                        $button.removeClass('bd-loading').prop('disabled', false);
                        $button.text(originalText);
                        
                        if (response.success) {
                            const data = response.data;
                            let noticeClass = data.update_available ? 'success' : 'info';
                            let message = data.update_available
                                ? `🎉 Ny versjon tilgjengelig: ${data.latest_version}! Gå til Plugin-siden for å oppdatere.`
                                : `✅ Du har den nyeste versjonen (${data.current_version})`;
                            
                            $button.after(`<div class="bd-update-notice ${noticeClass}">${message}</div>`);
                            
                            // Auto-hide notice after 8 seconds
                            setTimeout(() => {
                                $card.find('.bd-update-notice').fadeOut(300, function() {
                                    $(this).remove();
                                });
                            }, 8000);
                            
                        } else {
                            $button.after(`<div class="bd-update-notice error">❌ ${response.data || 'Kunne ikke sjekke for oppdateringer'}</div>`);
                        }
                    },
                    error: function() {
                        // Reset button state
                        $button.removeClass('bd-loading').prop('disabled', false);
                        $button.text(originalText);
                        
                        $button.after('<div class="bd-update-notice error">❌ Nettverksfeil ved sjekking av oppdateringer</div>');
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// AJAX handler for menu update checks
if (!function_exists('bd_handle_menu_update_check')) {
    add_action('wp_ajax_bd_menu_check_updates', 'bd_handle_menu_update_check');
    
    function bd_handle_menu_update_check() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'bd_menu_updates')) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $plugin_file = sanitize_text_field($_POST['plugin_file']);
        if (empty($plugin_file)) {
            wp_send_json_error('Invalid plugin file');
        }
        
        try {
            // Get plugin data
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_file);
            $current_version = $plugin_data['Version'];
            
            // Extract GitHub repo from Update URI or Plugin URI
            $update_uri = $plugin_data['UpdateURI'] ?? $plugin_data['PluginURI'] ?? '';
            if (empty($update_uri) || strpos($update_uri, 'github.com') === false) {
                wp_send_json_error('Plugin does not have GitHub integration');
            }
            
            // Extract owner/repo from GitHub URL
            preg_match('/github\.com\/([^\/]+)\/([^\/]+)/', $update_uri, $matches);
            if (count($matches) < 3) {
                wp_send_json_error('Could not parse GitHub repository');
            }
            
            $owner = $matches[1];
            $repo = $matches[2];
            
            // Check GitHub for latest release
            $api_url = "https://api.github.com/repos/{$owner}/{$repo}/releases/latest";
            $request = wp_remote_get($api_url, [
                'timeout' => 15,
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'BD-Menu-Helper/1.0',
                ]
            ]);
            
            if (is_wp_error($request)) {
                wp_send_json_error('Could not connect to GitHub: ' . $request->get_error_message());
            }
            
            $response_code = wp_remote_retrieve_response_code($request);
            if ($response_code !== 200) {
                wp_send_json_error('GitHub API error: HTTP ' . $response_code);
            }
            
            $body = wp_remote_retrieve_body($request);
            $data = json_decode($body, true);
            
            if (!$data || !isset($data['tag_name'])) {
                wp_send_json_error('Invalid response from GitHub API');
            }
            
            $latest_version = ltrim($data['tag_name'], 'v');
            $update_available = version_compare($current_version, $latest_version, '<');
            
            // Clear WordPress update cache to force refresh
            delete_site_transient('update_plugins');
            
            $response = [
                'current_version' => $current_version,
                'latest_version' => $latest_version,
                'update_available' => $update_available,
                'plugin_name' => $plugin_data['Name'],
                'release_date' => $data['published_at'] ?? '',
                'release_notes' => wp_trim_words(strip_tags($data['body'] ?? ''), 30),
                'download_url' => $data['html_url'] ?? '',
            ];
            
            wp_send_json_success($response);
            
        } catch (Exception $e) {
            wp_send_json_error('Error checking for updates: ' . $e->getMessage());
        }
    }
}
