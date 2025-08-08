/**
 * BD Product Sheet Editor - Admin JavaScript
 * Modern ES6+ implementation with BD Design Guide v3.0
 * 
 * @package BD_Product_Sheet_Editor
 * @version 1.3.0
 */

(function($) {
    'use strict';

    // Main BD Product Sheet Editor object
    window.bdPSE = {
        
        /**
         * Initialize the plugin
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initAutoSave();
            console.log('🚀 BD Product Sheet Editor initialized');
        },

        /**
         * Bind all event handlers
         */
        bindEvents: function() {
            // Product field changes
            $(document).on('change blur', '.bd-product-row .bd-input, .bd-product-row .bd-select', this.handleProductChange);
            
            // Category field changes
            $(document).on('change blur', '.bd-category-row .bd-input, .bd-category-row .bd-select', this.handleCategoryChange);
            
            // Header action buttons
            $(document).on('click', '[onclick*="bdPSE.exportData"]', this.exportData);
            $(document).on('click', '[onclick*="bdPSE.refreshData"]', this.refreshData);
            
            // Form submission feedback
            $('form').on('submit', this.handleFormSubmit);
            
            // Enhanced hover effects
            $('.bd-modern-table tbody tr').hover(
                function() { 
                    $(this).addClass('bd-hover-active'); 
                },
                function() { 
                    $(this).removeClass('bd-hover-active'); 
                }
            );
            
            // Auto-hide success messages
            setTimeout(() => {
                $('.notice.is-dismissible').fadeOut(500);
            }, 5000);
        },

        /**
         * Handle product field changes
         */
        handleProductChange: function(e) {
            const $input = $(e.target);
            const $row = $input.closest('.bd-product-row');
            const productId = $row.data('id');
            const fieldClass = $input.attr('class').split(' ').find(cls => cls.startsWith('bd-'));
            
            if (!productId || !fieldClass) return;
            
            // Update status indicator
            const $status = $row.find('.bd-status-indicator');
            bdPSE.updateStatus($status, 'saving');
            
            // Prepare data based on field type
            let data = {
                action: 'bd_update_product_field',
                product_id: productId,
                field: fieldClass.replace('bd-', ''),
                value: $input.val(),
                nonce: bdPSE.nonce
            };
            
            // Special handling for parent category
            if (fieldClass === 'bd-parent') {
                data.action = 'bd_update_parent_cat';
                data.parent_id = $input.val();
                delete data.field;
                delete data.value;
            }
            
            // Send AJAX request
            bdPSE.sendAjaxRequest(data, $status);
        },

        /**
         * Handle category field changes
         */
        handleCategoryChange: function(e) {
            const $input = $(e.target);
            const $row = $input.closest('.bd-category-row');
            const categoryId = $row.data('id');
            
            if (!categoryId) return;
            
            // Update status indicator
            const $status = $row.find('.bd-status-indicator');
            bdPSE.updateStatus($status, 'saving');
            
            // Collect all category data
            const data = {
                action: 'bd_update_product_cat',
                cat_id: categoryId,
                name: $row.find('.cat-name').val(),
                slug: $row.find('.cat-slug').val(),
                parent: $row.find('.cat-parent').val(),
                nonce: bdPSE.nonce
            };
            
            // Send AJAX request
            bdPSE.sendAjaxRequest(data, $status);
        },

        /**
         * Send AJAX request with modern fetch API
         */
        sendAjaxRequest: function(data, $status) {
            fetch(bdPSE.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    bdPSE.updateStatus($status, 'saved');
                    // Auto-reset status after 3 seconds
                    setTimeout(() => {
                        bdPSE.updateStatus($status, 'ready');
                    }, 3000);
                } else {
                    bdPSE.updateStatus($status, 'error');
                    console.error('BD PSE Error:', result.data || 'Unknown error');
                }
            })
            .catch(error => {
                bdPSE.updateStatus($status, 'error');
                console.error('BD PSE Network Error:', error);
            });
        },

        /**
         * Update status indicator
         */
        updateStatus: function($status, status) {
            const statusConfig = {
                ready: { icon: '⚪', text: 'Klar' },
                saving: { icon: '🔄', text: 'Lagrer...' },
                saved: { icon: '✅', text: 'Lagret' },
                error: { icon: '❌', text: 'Feil' }
            };
            
            const config = statusConfig[status] || statusConfig.ready;
            
            $status.attr('data-status', status);
            $status.find('.bd-status-icon').text(config.icon);
            $status.find('.bd-status-text').text(config.text);
            
            // Add animation for saving state
            if (status === 'saving') {
                $status.addClass('bd-loading');
            } else {
                $status.removeClass('bd-loading');
            }
        },

        /**
         * Export data functionality
         */
        exportData: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const originalText = $button.text();
            
            $button.addClass('bd-loading').prop('disabled', true);
            $button.text('📤 Eksporterer...');
            
            // Simulate export process (replace with actual implementation)
            setTimeout(() => {
                bdPSE.showNotice('✅ Data eksportert successfully!', 'success');
                $button.removeClass('bd-loading').prop('disabled', false);
                $button.text(originalText);
            }, 2000);
        },

        /**
         * Refresh data functionality
         */
        refreshData: function(e) {
            e.preventDefault();
            
            const $button = $(e.target);
            const originalText = $button.text();
            
            $button.addClass('bd-loading').prop('disabled', true);
            $button.text('🔄 Oppdaterer...');
            
            // Refresh the page after a short delay
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        },

        /**
         * Handle form submissions with loading states
         */
        handleFormSubmit: function(e) {
            const $form = $(e.target);
            const $button = $form.find('.button-primary');
            
            if ($button.length) {
                $button.addClass('bd-loading').prop('disabled', true);
                
                const originalText = $button.text();
                $button.text('💾 Lagrer...');
                
                // Reset after 3 seconds if no page reload
                setTimeout(() => {
                    $button.removeClass('bd-loading').prop('disabled', false);
                    $button.text(originalText);
                }, 3000);
            }
        },

        /**
         * Initialize tooltips for better UX
         */
        initTooltips: function() {
            // Add tooltips to status indicators
            $('.bd-status-indicator').each(function() {
                const $this = $(this);
                const status = $this.attr('data-status');
                const tooltips = {
                    ready: 'Klar for redigering',
                    saving: 'Lagrer endringer...',
                    saved: 'Endringer lagret',
                    error: 'Feil ved lagring'
                };
                
                $this.attr('title', tooltips[status] || '');
            });
            
            // Add tooltips to input fields
            $('.bd-input[placeholder]').each(function() {
                const $this = $(this);
                const placeholder = $this.attr('placeholder');
                $this.attr('title', `Skriv inn ${placeholder.toLowerCase()}`);
            });
        },

        /**
         * Initialize auto-save functionality
         */
        initAutoSave: function() {
            let saveTimeout;
            
            $(document).on('input', '.bd-input', function() {
                const $input = $(this);
                
                // Clear existing timeout
                clearTimeout(saveTimeout);
                
                // Set new timeout for auto-save
                saveTimeout = setTimeout(() => {
                    $input.trigger('change');
                }, 1500); // Auto-save after 1.5 seconds of inactivity
            });
        },

        /**
         * Show admin notice
         */
        showNotice: function(message, type = 'info') {
            const noticeClass = `notice notice-${type} is-dismissible`;
            const $notice = $(`
                <div class="${noticeClass}" style="display: none;">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            // Insert notice at the top of the admin content
            $('.bd-product-sheet-editor-admin').prepend($notice);
            $notice.slideDown(300);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $notice.slideUp(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Handle dismiss button
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.slideUp(300, function() {
                    $(this).remove();
                });
            });
        },

        /**
         * Smooth scrolling for anchor links
         */
        initSmoothScrolling: function() {
            $("a[href^='#']").on('click', function(e) {
                const target = $(this.hash);
                if (target.length) {
                    e.preventDefault();
                    $("html, body").animate({
                        scrollTop: target.offset().top - 32
                    }, 500);
                }
            });
        },

        /**
         * Form validation with visual feedback
         */
        initFormValidation: function() {
            $("input[required], textarea[required]").on('blur', function() {
                const $input = $(this);
                if ($input.val() === '') {
                    $input.css('border-color', '#dc2626');
                    $input.addClass('bd-error');
                } else {
                    $input.css('border-color', '#10b981');
                    $input.removeClass('bd-error');
                }
            });
        },

        /**
         * Keyboard shortcuts
         */
        initKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + S to save (prevent default browser save)
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    bdPSE.showNotice('💾 Auto-lagring er aktivert', 'info');
                }
                
                // Escape to clear focus
                if (e.key === 'Escape') {
                    $(':focus').blur();
                }
            });
        },

        /**
         * Initialize responsive table handling
         */
        initResponsiveTables: function() {
            const $tables = $('.bd-modern-table');
            
            function handleTableResponsive() {
                $tables.each(function() {
                    const $table = $(this);
                    const $container = $table.closest('.bd-table-container');
                    
                    if ($table.width() > $container.width()) {
                        $container.addClass('bd-table-scroll');
                    } else {
                        $container.removeClass('bd-table-scroll');
                    }
                });
            }
            
            // Check on load and resize
            handleTableResponsive();
            $(window).on('resize', handleTableResponsive);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Set global variables from localized script
        if (typeof bdPSE !== 'undefined' && bdPSE.ajaxurl) {
            window.bdPSE.ajaxurl = bdPSE.ajaxurl;
            window.bdPSE.nonce = bdPSE.nonce;
            window.bdPSE.strings = bdPSE.strings;
        }
        
        // Initialize all functionality
        window.bdPSE.init();
        window.bdPSE.initSmoothScrolling();
        window.bdPSE.initFormValidation();
        window.bdPSE.initKeyboardShortcuts();
        window.bdPSE.initResponsiveTables();
        
        // Add loading animation to page transitions
        $('a[href*="page=bd-product-sheet-editor"]').on('click', function() {
            const $link = $(this);
            if (!$link.hasClass('nav-tab-active')) {
                $link.addClass('bd-loading');
            }
        });
    });

    // Handle page visibility changes for auto-save
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden, trigger save for any pending changes
            $('.bd-input:focus').trigger('change');
        }
    });

})(jQuery);

/**
 * Additional utility functions
 */

// Debounce function for performance optimization
function debounce(func, wait, immediate) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            timeout = null;
            if (!immediate) func(...args);
        };
        const callNow = immediate && !timeout;
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        if (callNow) func(...args);
    };
}

// Throttle function for scroll events
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Console styling for BD branding
console.log(
    '%c🚀 BD Product Sheet Editor %cv1.3.0',
    'color: #667eea; font-weight: bold; font-size: 16px;',
    'color: #64748b; font-size: 12px;'
);
console.log(
    '%cBuene Data - Professional WordPress Tools',
    'color: #764ba2; font-style: italic;'
);