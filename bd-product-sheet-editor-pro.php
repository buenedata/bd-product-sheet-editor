<?php
/*
Plugin Name: BD Product Sheet Editor Pro
Description: 📊 Spreadsheet editor for WooCommerce products, categories, and brands.
Version: 2.0.0
Author: Buene Data
Author URI: https://buenedata.no
Plugin URI: https://github.com/buenedata/bd-product-sheet-editor
Update URI: https://github.com/buenedata/bd-product-sheet-editor
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Network: false
Text Domain: bd-product-sheet-editor
Domain Path: /languages
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('BD_PSE_VERSION', '2.0.0');
define('BD_PSE_FILE', __FILE__);
define('BD_PSE_PATH', plugin_dir_path(__FILE__));
define('BD_PSE_URL', plugin_dir_url(__FILE__));
define('BD_PSE_BASENAME', plugin_basename(__FILE__));

// Initialize updater
if (is_admin()) {
    require_once BD_PSE_PATH . 'includes/class-bd-updater.php';
    new BD_Plugin_Updater(BD_PSE_FILE, 'buenedata', 'bd-product-sheet-editor');
}

// Initialize update server
require_once BD_PSE_PATH . 'includes/class-bd-update-server.php';

// Load menu helper
require_once BD_PSE_PATH . 'bd-menu-helper.php';

// Use BD Menu Helper instead of creating separate menu
add_action('admin_menu', function () {
    bd_add_buene_data_menu(
        'Product Sheet Editor',
        'bd-product-sheet-editor',
        'bd_product_sheet_editor_page',
        '📊'
    );
});

// Enqueue admin styles and scripts
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'bd-product-sheet-editor') === false) {
        return;
    }
    
    wp_enqueue_style(
        'bd-pse-admin',
        BD_PSE_URL . 'assets/css/admin.css',
        [],
        BD_PSE_VERSION
    );
    
    wp_enqueue_script(
        'bd-pse-admin',
        BD_PSE_URL . 'assets/js/admin.js',
        ['jquery'],
        BD_PSE_VERSION,
        true
    );
    
    wp_localize_script('bd-pse-admin', 'bdPSE', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('bd_pse_nonce'),
        'strings' => [
            'saving' => __('Lagrer...', 'bd-product-sheet-editor'),
            'saved' => __('✓ Lagret', 'bd-product-sheet-editor'),
            'error' => __('❌ Feil', 'bd-product-sheet-editor'),
        ]
    ]);
});

function bd_product_sheet_editor_page() {
    if (!current_user_can('manage_woocommerce')) {
        wp_die(__('Du har ikke tillatelse til å se denne siden.', 'bd-product-sheet-editor'));
    }

    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'products';
    $tabs = [
        'products' => ['label' => 'Produkter', 'icon' => '🛍️'],
        'categories' => ['label' => 'Kategorier', 'icon' => '📁'],
        'brands' => ['label' => 'Merker', 'icon' => '🏷️']
    ];

    ?>
    <div class="wrap bd-product-sheet-editor-admin">
        <!-- Modern Header Section -->
        <div class="bd-admin-header">
            <div class="bd-branding">
                <h2>📊 Product Sheet Editor</h2>
                <p>Avansert redigering av WooCommerce produkter, kategorier og merker</p>
            </div>
            <div class="bd-actions">
                <button type="button" class="button button-primary" onclick="bdPSE.exportData()">
                    📤 Eksporter data
                </button>
                <button type="button" class="button button-secondary" onclick="bdPSE.refreshData()">
                    🔄 Oppdater
                </button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <nav class="nav-tab-wrapper">
            <?php foreach ($tabs as $slug => $tab_data): ?>
                <a href="?page=bd-product-sheet-editor&tab=<?php echo esc_attr($slug); ?>"
                   class="nav-tab <?php echo ($tab === $slug) ? 'nav-tab-active' : ''; ?>">
                    <?php echo $tab_data['icon'] . ' ' . esc_html($tab_data['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <!-- Tab Content -->
        <div id="tab-<?php echo esc_attr($tab); ?>" class="tab-content active">
            <?php

            if ($tab === 'products') {
                bd_render_products_tab();
            }

            elseif ($tab === 'categories') {
                bd_render_categories_tab();
            }
            elseif ($tab === 'brands') {
                bd_render_brands_tab();
            }
            ?>
        </div>
    </div>

    <?php
}

/**
 * Render products tab content
 */
function bd_render_products_tab() {
    $args = ['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'any'];
    $products = get_posts($args);
    $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

    if (empty($products)) {
        echo '<div class="bd-info-box"><p>Ingen produkter funnet. Opprett produkter i WooCommerce først.</p></div>';
        return;
    }

    $cat_options = '<option value="0">Ingen kategori</option>';
    foreach ($categories as $cat) {
        $cat_options .= '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
    }
    ?>

    <div class="bd-settings-section">
        <h3>🛍️ Produktredigering</h3>
        <p>Rediger produktinformasjon direkte i tabellen. Endringer lagres automatisk.</p>
        
        <div class="bd-table-container">
            <table class="bd-modern-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produktnavn</th>
                        <th>Pris (kr)</th>
                        <th>Tilbudspris (kr)</th>
                        <th>SKU</th>
                        <th>Lager</th>
                        <th>Hovedkategori</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product_post):
                        $product = wc_get_product($product_post->ID);
                        if (!$product) continue;
                        
                        $terms = wp_get_post_terms($product->get_id(), 'product_cat');
                        $current_cat = isset($terms[0]) ? $terms[0] : null;
                        $current_parent = $current_cat ? $current_cat->parent : 0;
                        ?>
                        <tr data-id="<?php echo esc_attr($product->get_id()); ?>" class="bd-product-row">
                            <td class="bd-id-cell"><?php echo $product->get_id(); ?></td>
                            <td>
                                <input type="text"
                                       class="bd-input bd-title"
                                       value="<?php echo esc_attr($product->get_name()); ?>"
                                       placeholder="Produktnavn">
                            </td>
                            <td>
                                <input type="number"
                                       class="bd-input bd-price"
                                       value="<?php echo esc_attr($product->get_regular_price()); ?>"
                                       placeholder="0.00"
                                       step="0.01">
                            </td>
                            <td>
                                <input type="number"
                                       class="bd-input bd-sale"
                                       value="<?php echo esc_attr($product->get_sale_price()); ?>"
                                       placeholder="0.00"
                                       step="0.01">
                            </td>
                            <td>
                                <input type="text"
                                       class="bd-input bd-sku"
                                       value="<?php echo esc_attr($product->get_sku()); ?>"
                                       placeholder="SKU">
                            </td>
                            <td>
                                <input type="number"
                                       class="bd-input bd-stock"
                                       value="<?php echo esc_attr($product->get_stock_quantity()); ?>"
                                       placeholder="0">
                            </td>
                            <td>
                                <select class="bd-select bd-parent">
                                    <?php echo str_replace('value="' . $current_parent . '"', 'value="' . $current_parent . '" selected', $cat_options); ?>
                                </select>
                            </td>
                            <td>
                                <span class="bd-status-indicator" data-status="ready">
                                    <span class="bd-status-icon">⚪</span>
                                    <span class="bd-status-text">Klar</span>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Render categories tab content
 */
function bd_render_categories_tab() {
    $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

    if (empty($categories)) {
        echo '<div class="bd-info-box"><p>Ingen kategorier funnet. Opprett kategorier i WooCommerce først.</p></div>';
        return;
    }
    ?>

    <div class="bd-settings-section">
        <h3>📁 Kategoriredigering</h3>
        <p>Administrer produktkategorier og deres hierarki.</p>
        
        <div class="bd-table-container">
            <table class="bd-modern-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Kategorinavn</th>
                        <th>Slug</th>
                        <th>Foreldrekategori</th>
                        <th>Antall produkter</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat):
                        $parent_options = '<option value="0">Ingen forelder</option>';
                        foreach ($categories as $p) {
                            if ($p->term_id == $cat->term_id) continue;
                            $selected = ($cat->parent == $p->term_id) ? 'selected' : '';
                            $parent_options .= "<option value='{$p->term_id}' $selected>{$p->name}</option>";
                        }
                        ?>
                        <tr data-id="<?php echo esc_attr($cat->term_id); ?>" class="bd-category-row">
                            <td class="bd-id-cell"><?php echo $cat->term_id; ?></td>
                            <td>
                                <input type="text"
                                       class="bd-input cat-name"
                                       value="<?php echo esc_attr($cat->name); ?>"
                                       placeholder="Kategorinavn">
                            </td>
                            <td>
                                <input type="text"
                                       class="bd-input cat-slug"
                                       value="<?php echo esc_attr($cat->slug); ?>"
                                       placeholder="kategori-slug">
                            </td>
                            <td>
                                <select class="bd-select cat-parent">
                                    <?php echo $parent_options; ?>
                                </select>
                            </td>
                            <td class="bd-count-cell">
                                <span class="bd-label"><?php echo $cat->count; ?> produkter</span>
                            </td>
                            <td>
                                <span class="bd-status-indicator" data-status="ready">
                                    <span class="bd-status-icon">⚪</span>
                                    <span class="bd-status-text">Klar</span>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

/**
 * Render brands tab content
 */
function bd_render_brands_tab() {
    ?>
    <div class="bd-settings-section">
        <h3>🏷️ Merkeredigering</h3>
        <div class="bd-info-box">
            <p><strong>🚧 Under utvikling</strong></p>
            <p>Merkeredigering kommer i neste versjon av BD Product Sheet Editor.</p>
            <p>Planlagte funksjoner:</p>
            <ul>
                <li>Opprett og rediger produktmerker</li>
                <li>Tilordne merker til produkter</li>
                <li>Masseoppdatering av merker</li>
                <li>Import/eksport av merkedata</li>
            </ul>
        </div>
        
        <div class="bd-coming-soon">
            <div class="bd-coming-soon-icon">🏷️</div>
            <h4>Kommer snart!</h4>
            <p>Vi jobber med å implementere avansert merkehåndtering.</p>
            <a href="https://github.com/buenedata/bd-product-sheet-editor" target="_blank" class="button button-primary">
                📋 Følg utviklingen på GitHub
            </a>
        </div>
    </div>
    <?php
}

}

// AJAX Handlers
add_action('wp_ajax_bd_update_product_field', 'bd_handle_product_field_update');
add_action('wp_ajax_bd_update_parent_cat', 'bd_handle_parent_category_update');
add_action('wp_ajax_bd_update_product_cat', 'bd_handle_category_update');

/**
 * Handle product field updates
 */
function bd_handle_product_field_update() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'bd_pse_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    // Check permissions
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $product_id = intval($_POST['product_id']);
    $field = sanitize_text_field($_POST['field']);
    $value = sanitize_text_field($_POST['value']);
    
    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(['message' => 'Product not found']);
    }
    
    try {
        switch ($field) {
            case 'title':
                $product->set_name($value);
                break;
            case 'price':
                $product->set_regular_price($value);
                break;
            case 'sale':
                $product->set_sale_price($value);
                break;
            case 'sku':
                $product->set_sku($value);
                break;
            case 'stock':
                $product->set_stock_quantity($value);
                break;
            default:
                wp_send_json_error(['message' => 'Invalid field']);
        }
        
        $product->save();
        wp_send_json_success(['message' => 'Product updated successfully']);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

/**
 * Handle parent category updates
 */
function bd_handle_parent_category_update() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'bd_pse_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    // Check permissions
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $product_id = intval($_POST['product_id']);
    $parent_id = intval($_POST['parent_id']);
    
    $terms = wp_get_post_terms($product_id, 'product_cat');
    if (!empty($terms)) {
        $term = $terms[0];
        $result = wp_update_term($term->term_id, 'product_cat', ['parent' => $parent_id]);
        
        if (!is_wp_error($result)) {
            wp_send_json_success(['message' => 'Category parent updated']);
        } else {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
    } else {
        wp_send_json_error(['message' => 'No categories found for product']);
    }
}

/**
 * Handle category updates
 */
function bd_handle_category_update() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'bd_pse_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
    }
    
    // Check permissions
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    $term_id = intval($_POST['cat_id']);
    $name = sanitize_text_field($_POST['name']);
    $slug = sanitize_title($_POST['slug']);
    $parent = intval($_POST['parent']);
    
    $result = wp_update_term($term_id, 'product_cat', [
        'name' => $name,
        'slug' => $slug,
        'parent' => $parent
    ]);
    
    if (!is_wp_error($result)) {
        wp_send_json_success(['message' => 'Category updated successfully']);
    } else {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
}
