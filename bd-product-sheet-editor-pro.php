<?php
/*
Plugin Name: BD Product Sheet Editor Pro
Description: Spreadsheet editor for WooCommerce products, categories, and brands.
Version: 1.2
Author: Buene Data
*/

add_action('admin_menu', function () {
    add_menu_page(
        'BD Product Sheet Editor',
        'BD Product Sheet Editor',
        'manage_woocommerce',
        'bd-product-sheet-editor',
        'bd_product_sheet_editor_page',
        'dashicons-edit',
        26
    );
});

function bd_product_sheet_editor_page() {
    if (!current_user_can('manage_woocommerce')) return;

    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'products';
    $tabs = ['products' => 'Produkter', 'categories' => 'Kategorier', 'brands' => 'Merker'];

    echo '<div class="wrap"><h1>BD Product Sheet Editor</h1><nav style="margin-bottom:1em;">';
    foreach ($tabs as $slug => $label) {
        $active = ($tab === $slug) ? 'style="font-weight:bold;"' : '';
        echo "<a href='?page=bd-product-sheet-editor&tab=$slug' $active>$label</a> | ";
    }
    echo '</nav>';

    echo '<style>
        table.bd-sheet { width: 100%; border-collapse: collapse; }
        table.bd-sheet th, table.bd-sheet td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        table.bd-sheet th { background: #f1f1f1; }
        input[type=text], select { width: 100%; }
        .status-updated { color: green; font-size: 12px; margin-left: 8px; }
    </style>';

    if ($tab === 'products') {
        $args = ['post_type' => 'product', 'posts_per_page' => -1];
        $products = get_posts($args);
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

        $cat_options = '<option value="0">Ingen</option>';
        foreach ($categories as $cat) {
            $cat_options .= '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
        }

        echo '<table class="bd-sheet"><thead><tr>
            <th>ID</th><th>Navn</th><th>Pris</th><th>Tilbudspris</th><th>SKU</th><th>Lager</th><th>Foreldrekategori</th>
        </tr></thead><tbody>';

        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            $terms = wp_get_post_terms($product->get_id(), 'product_cat');
            $current_cat = isset($terms[0]) ? $terms[0] : null;
            $current_parent = $current_cat ? $current_cat->parent : 0;

            echo '<tr data-id="' . $product->get_id() . '">';
            echo '<td>' . $product->get_id() . '</td>';
            echo '<td><input type="text" class="bd-title" value="' . esc_attr($product->get_name()) . '"></td>';
            echo '<td><input type="text" class="bd-price" value="' . esc_attr($product->get_regular_price()) . '"></td>';
            echo '<td><input type="text" class="bd-sale" value="' . esc_attr($product->get_sale_price()) . '"></td>';
            echo '<td><input type="text" class="bd-sku" value="' . esc_attr($product->get_sku()) . '"></td>';
            echo '<td><input type="text" class="bd-stock" value="' . esc_attr($product->get_stock_quantity()) . '"></td>';
            echo '<td><select class="bd-parent">' . str_replace('value="' . $current_parent . '"', 'value="' . $current_parent . '" selected', $cat_options) . '</select><span class="status-updated"></span></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    if ($tab === 'categories') {
        $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

        echo '<table class="bd-sheet"><thead><tr>
            <th>ID</th><th>Navn</th><th>Slug</th><th>Forelder</th><th>Status</th>
        </tr></thead><tbody>';

        foreach ($categories as $cat) {
            $parent_options = '<option value="0">Ingen</option>';
            foreach ($categories as $p) {
                if ($p->term_id == $cat->term_id) continue;
                $selected = ($cat->parent == $p->term_id) ? 'selected' : '';
                $parent_options .= "<option value='{$p->term_id}' $selected>{$p->name}</option>";
            }

            echo "<tr data-id='{$cat->term_id}'>
                <td>{$cat->term_id}</td>
                <td><input type='text' class='cat-name' value='" . esc_attr($cat->name) . "'></td>
                <td><input type='text' class='cat-slug' value='" . esc_attr($cat->slug) . "'></td>
                <td><select class='cat-parent'>$parent_options</select></td>
                <td><span class='status-updated'></span></td>
            </tr>";
        }
        echo '</tbody></table>';
    }

    if ($tab === 'brands') {
        echo '<p>Merke-redigering kommer i neste versjon.</p>';
    }

    echo '<script>
    document.querySelectorAll(".bd-parent").forEach(select => {
        select.addEventListener("change", function() {
            const row = this.closest("tr");
            const id = row.dataset.id;
            const parent_id = this.value;
            const span = this.nextElementSibling;
            span.textContent = "Lagrer...";

            fetch(ajaxurl, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    action: "bd_update_parent_cat",
                    product_id: id,
                    parent_id: parent_id
                })
            }).then(res => res.json()).then(data => {
                span.textContent = data.success ? "✓" : "Feil";
            });
        });
    });

    document.querySelectorAll(".cat-name, .cat-slug, .cat-parent").forEach(el => {
        el.addEventListener("change", function() {
            const row = this.closest("tr");
            const id = row.dataset.id;
            const name = row.querySelector(".cat-name").value;
            const slug = row.querySelector(".cat-slug").value;
            const parent = row.querySelector(".cat-parent").value;
            const status = row.querySelector(".status-updated");
            status.textContent = "Lagrer...";

            fetch(ajaxurl, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    action: "bd_update_product_cat",
                    cat_id: id,
                    name: name,
                    slug: slug,
                    parent: parent
                })
            }).then(res => res.json()).then(data => {
                status.textContent = data.success ? "✓" : "Feil";
            });
        });
    });
    </script>';
}

add_action('wp_ajax_bd_update_parent_cat', function () {
    $product_id = intval($_POST['product_id']);
    $parent_id = intval($_POST['parent_id']);
    $terms = wp_get_post_terms($product_id, 'product_cat');
    if (!empty($terms)) {
        $term = $terms[0];
        wp_update_term($term->term_id, 'product_cat', ['parent' => $parent_id]);
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
});

add_action('wp_ajax_bd_update_product_cat', function () {
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
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
});
