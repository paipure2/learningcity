<?php
/**
 * ACF Flexible Content Keyboard Shortcut
 * Press "/" to trigger Add Row button
 */

function enqueue_acf_flexible_keyboard_script() {
    // Only load on admin pages
    if (!is_admin()) {
        return;
    }

    ?>
    <script type="text/javascript">
    (function($) {
        'use strict';

        $(document).ready(function() {
            var isProcessing = false;
            var debounceTimer = null;

            // Listen for keydown event on document
            $(document).on('keydown', function(e) {
                // Check if "/" key is pressed (key code 191 or key "/")
                if ((e.keyCode === 191 || e.key === '/') && !e.ctrlKey && !e.metaKey && !e.altKey) {

                    // Prevent rapid firing
                    if (isProcessing) {
                        e.preventDefault();
                        return false;
                    }

                    // Check if modal is already open
                    var $modal = $('.acf-fc-popup, .acfe-modal, .-open');
                    if ($modal.length > 0 && $modal.is(':visible')) {
                        return; // Modal is already open, don't trigger again
                    }

                    // Check if user is typing in an input, textarea, or contenteditable element
                    var $target = $(e.target);
                    var isTyping = $target.is('input, textarea, select') ||
                                   $target.is('[contenteditable="true"]') ||
                                   $target.closest('.acf-input').length > 0 ||
                                   $target.closest('.acf-field').length > 0;

                    // Only trigger if not typing in a field
                    if (!isTyping) {
                        // Find the ACF flexible content "Add Row" button
                        var $addButton = $('.acf-flexible-content .acf-actions a.acf-button[data-name="add-layout"]');

                        // Filter to only visible buttons in active tabs
                        $addButton = $addButton.filter(function() {
                            var $button = $(this);
                            var $flexibleField = $button.closest('.acf-field-flexible-content');

                            // Simple visibility check first
                            if (!$flexibleField.is(':visible') || $flexibleField.hasClass('acf-hidden')) {
                                return false;
                            }

                            // Check if this flexible content is inside a tab
                            // Look for the tab group wrapper
                            var $postBody = $flexibleField.closest('.acf-fields');
                            var $tabGroup = $postBody.find('.acf-tab-group').first();

                            if ($tabGroup.length > 0) {
                                // Find which tab is currently active
                                var $activeTabButton = $tabGroup.find('.acf-tab-button.active');

                                if ($activeTabButton.length > 0) {
                                    var activeTabKey = $activeTabButton.data('key');

                                    // Find the closest preceding tab field for this flexible content
                                    // This works for both direct fields and cloned fields
                                    var $precedingTab = null;
                                    var $currentField = $flexibleField;

                                    // Walk backwards through siblings to find the tab
                                    while ($currentField.length > 0) {
                                        var $prevTab = $currentField.prevAll('.acf-field-tab').first();

                                        if ($prevTab.length > 0) {
                                            $precedingTab = $prevTab;
                                            break;
                                        }

                                        // If not found, check parent for cloned fields
                                        var $parent = $currentField.parent().closest('.acf-field');
                                        if ($parent.length > 0 && $parent.hasClass('acf-field-clone')) {
                                            $currentField = $parent;
                                        } else {
                                            break;
                                        }
                                    }

                                    if ($precedingTab && $precedingTab.length > 0) {
                                        var fieldTabKey = $precedingTab.data('key');
                                        return fieldTabKey === activeTabKey;
                                    }
                                }
                            }

                            // If not in a tab, it's always accessible
                            return true;
                        });

                        if ($addButton.length > 0) {
                            e.preventDefault();

                            // Set processing flag
                            isProcessing = true;

                            // Clear any existing debounce timer
                            if (debounceTimer) {
                                clearTimeout(debounceTimer);
                            }

                            // Trigger click on the first visible Add Row button
                            $addButton.first().trigger('click');

                            // Reset processing flag after a short delay
                            debounceTimer = setTimeout(function() {
                                isProcessing = false;
                            }, 500); // 500ms debounce

                            return false;
                        }
                    }
                }
            });
        });

    })(jQuery);
    </script>
    <?php
}
add_action('admin_footer', 'enqueue_acf_flexible_keyboard_script');
