/**
 * Admin script for Monthly Calendar Planner
 *
 * @version 1.0.2
 */
jQuery(document).ready(function($) {
    const builderContainer = $('#mk-mcp-builder-wrapper');
    const itemsJsonInput = $('#mk_mcp_calendar_items_json');
    const viewModeSelect = $('#mk_mcp_view_mode');
    const tableSettings = $('#mk-mcp-table-settings');
    const columnCountSelect = $('#mk_mcp_column_count');
    const columnNameInputs = $('.mk-mcp-column-name-input');

    // Function to fetch and render the current view (calendar or table)
    function loadBuilderView() {
        const month = $('#mk_mcp_month').val();
        const year = $('#mk_mcp_year').val();
        const view_mode = viewModeSelect.val();
        const items = itemsJsonInput.val();
        
        let ajaxData = {
            action: 'mk_mcp_get_admin_builder_view',
            nonce: mk_mcp_ajax.nonce,
            month: month,
            year: year,
            view_mode: view_mode,
            items: items
        };

        if (view_mode === 'table') {
            ajaxData.column_count = columnCountSelect.val();
            ajaxData.column_names = JSON.stringify(
                columnNameInputs.map(function() { return $(this).val(); }).get()
            );
        }
        
        builderContainer.html('<div class="mk-mcp-loader"><p>Loading View...</p></div>');

        $.post(mk_mcp_ajax.ajax_url, ajaxData, function(response) {
            if (response.success) {
                builderContainer.html(response.data);
                initializeSortable();
            } else {
                builderContainer.html('<p>Error loading view.</p>');
            }
        });
    }

    // Function to serialize calendar/table data and update the hidden input
    function serializeData() {
        const data = {};
        const view_mode = viewModeSelect.val();

        if (view_mode === 'table') {
            $('.mk-mcp-admin-table tbody tr').each(function() {
                const day = $(this).data('day');
                if (day) {
                    data[day] = {};
                    $(this).find('.mk-mcp-day-items-wrapper').each(function() {
                        const col = $(this).data('col');
                        const items = [];
                        $(this).find('.mk-mcp-item').each(function() {
                            items.push({
                                title: $(this).find('.mk-mcp-item-title').val(),
                                text: $(this).find('.mk-mcp-item-text').val()
                            });
                        });
                        if (items.length > 0) {
                            data[day][col] = items;
                        }
                    });
                }
            });
        } else { // Calendar view
             $('.mk-mcp-day').each(function() {
                const day = $(this).data('day');
                if (day) {
                    data[day] = { 0: [] }; // Store all in column 0
                    const items = [];
                    $(this).find('.mk-mcp-item').each(function() {
                        items.push({
                            title: $(this).find('.mk-mcp-item-title').val(),
                            text: $(this).find('.mk-mcp-item-text').val()
                        });
                    });
                    if (items.length > 0) {
                        data[day][0] = items;
                    }
                }
            });
        }
        itemsJsonInput.val(JSON.stringify(data));
    }

    // Initialize jQuery UI Sortable for drag and drop
    function initializeSortable() {
        $('.mk-mcp-day-items-wrapper').sortable({
            connectWith: ".mk-mcp-day-items-wrapper",
            placeholder: "mk-mcp-item-placeholder",
            revert: true,
            update: function(event, ui) {
                serializeData();
            }
        }).disableSelection();
    }

    // --- Event Handlers ---

    // Load view on month/year change
    $('#mk_mcp_month, #mk_mcp_year').on('change', function() {
        serializeData(); 
        loadBuilderView();
    });

    // Handle view mode change
    viewModeSelect.on('change', function(){
        if ($(this).val() === 'table') {
            tableSettings.removeClass('settings-hidden');
        } else {
            tableSettings.addClass('settings-hidden');
        }
        serializeData();
        loadBuilderView();
    });

    // Handle column count change
    columnCountSelect.on('change', function() {
        const count = parseInt($(this).val(), 10);
        columnNameInputs.each(function(index) {
            $(this).toggle(index < count);
        });
        serializeData();
        loadBuilderView();
    });

    // Add new item
    builderContainer.on('click', '.mk-mcp-add-item-btn', function() {
        const itemsWrapper = $(this).siblings('.mk-mcp-day-items-wrapper');
        const newItemHtml = `
            <div class="mk-mcp-item" data-id="${new Date().getTime()}">
                <div class="mk-mcp-item-header">
                    <span class="mk-mcp-item-title-preview">New Item</span>
                    <div class="mk-mcp-item-actions">
                        <button type="button" class="mk-mcp-duplicate-item">D</button>
                        <button type="button" class="mk-mcp-delete-item">X</button>
                    </div>
                </div>
                <div class="mk-mcp-item-content" style="display: block;">
                    <input type="text" class="mk-mcp-item-title" placeholder="Title" value="New Item">
                    <textarea class="mk-mcp-item-text" placeholder="Text"></textarea>
                </div>
            </div>`;
        itemsWrapper.append(newItemHtml);
        serializeData();
    });

    // Delete item
    builderContainer.on('click', '.mk-mcp-delete-item', function() {
        if (confirm('Are you sure you want to delete this item?')) {
            $(this).closest('.mk-mcp-item').remove();
            serializeData();
        }
    });
    
    // Duplicate item
    builderContainer.on('click', '.mk-mcp-duplicate-item', function() {
        const originalItem = $(this).closest('.mk-mcp-item');
        const clonedItem = originalItem.clone();
        
        clonedItem.attr('data-id', new Date().getTime());
        clonedItem.find('.mk-mcp-item-title').val(originalItem.find('.mk-mcp-item-title').val());
        clonedItem.find('.mk-mcp-item-text').val(originalItem.find('.mk-mcp-item-text').val());
        
        originalItem.after(clonedItem);
        serializeData();
    });
    
    // Toggle item content view
    builderContainer.on('click', '.mk-mcp-item-header', function(e) {
         if ($(e.target).is('button')) return; // Don't toggle if a button was clicked
        $(this).siblings('.mk-mcp-item-content').slideToggle(200);
    });
    
    // Serialize on any input/change
    builderContainer.on('keyup change', 'input, textarea', serializeData);

    // Initial load
    loadBuilderView();
});
