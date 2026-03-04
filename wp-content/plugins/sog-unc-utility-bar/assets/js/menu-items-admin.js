/**
 * Menu Items Admin UI
 * Handles adding, removing, and reordering custom menu items
 * in the utility bar settings page.
 */
(function ($) {
    'use strict';

    $(function () {
        var $table = $('#ub-menu-items-table');
        var $tbody = $('#ub-menu-items-body');

        if (!$table.length) {
            return;
        }

        // Make rows sortable via drag handle
        $tbody.sortable({
            handle: '.ub-drag-handle',
            axis: 'y',
            helper: function (e, tr) {
                // Preserve column widths while dragging
                var $originals = tr.children();
                var $helper = tr.clone();
                $helper.children().each(function (index) {
                    $(this).width($originals.eq(index).outerWidth());
                });
                return $helper;
            },
            update: function () {
                reindex();
            }
        });

        // Add new custom menu item
        $('#ub-add-menu-item').on('click', function () {
            var nextIndex = $tbody.find('tr').length;
            var template = wp.template('ub-menu-item-row');
            var html = template({ index: nextIndex });
            $tbody.append(html);
        });

        // Remove custom menu item
        $tbody.on('click', '.ub-remove-item', function () {
            $(this).closest('tr').fadeOut(200, function () {
                $(this).remove();
                reindex();
            });
        });

        /**
         * Re-index all input name attributes after sort/remove
         * so the array keys are sequential.
         */
        function reindex() {
            $tbody.find('tr').each(function (i) {
                $(this).attr('data-index', i);
                $(this).find('input, select, textarea').each(function () {
                    var name = $(this).attr('name');
                    if (name) {
                        $(this).attr('name', name.replace(/\[\d+\]/, '[' + i + ']'));
                    }
                });
            });
        }
    });
})(jQuery);
