/**
 * Admin script for Monthly Calendar Planner
 *
 * @version 1.0.1 (Stable)
 */
jQuery(document).ready(function($) {
    const calendarContainer = $('#mk-mcp-calendar-grid-wrapper');
    const itemsJsonInput = $('#mk_mcp_calendar_items_json');

    // Function to fetch and render the calendar grid
    function loadCalendarGrid() {
        const month = $('#mk_mcp_month').val();
        const year = $('#mk_mcp_year').val();
        const items = itemsJsonInput.val();
        
        calendarContainer.html('<div class="mk-mcp-loader"><p>Loading Calendar...</p></div>');

        $.post(mk_mcp_ajax.ajax_url, {
            action: 'mk_mcp_get_admin_calendar_grid',
            nonce: mk_mcp_ajax.nonce,
            month: month,
            year: year,
            items: items
        }, function(response) {
            if (response.success) {
                calendarContainer.html(response.data);
                initializeSortable();
            } else {
                calendarContainer.html('<p>Error loading calendar.</p>');
            }
        });
    }

    // Function to serialize calendar data and update the hidden input
    function serializeCalendarData() {
        const data = {};
        $('.mk-mcp-day').each(function() {
            const day = $(this).data('day');
            if (day) {
                const items = [];
                $(this).find('.mk-mcp-item').each(function() {
                    const title = $(this).find('.mk-mcp-item-title').val();
                    const text = $(this).find('.mk-mcp-item-text').val();
                    items.push({
                        title: title,
                        text: text
                    });
                });
                if (items.length > 0) {
                    data[day] = items;
                }
            }
        });
        itemsJsonInput.val(JSON.stringify(data));
    }

    // Function to initialize jQuery UI Sortable (drag and drop)
    function initializeSortable() {
        $('.mk-mcp-day-items-wrapper').sortable({
            connectWith: ".mk-mcp-day-items-wrapper",
            placeholder: "mk-mcp-item-placeholder",
            revert: true,
            update: function(event, ui) {
                serializeCalendarData();
            }
        }).disableSelection();
    }

    // Event handlers
    $('#mk_mcp_month, #mk_mcp_year').on('change', function() {
        serializeCalendarData(); // Save current state before reloading
        loadCalendarGrid();
    });

    // Add new item
    calendarContainer.on('click', '.mk-mcp-add-item-btn', function() {
        const itemsWrapper = $(this).siblings('.mk-mcp-day-items-wrapper');
        const newItem = `
            <div class="mk-mcp-item" data-id="${new Date().getTime()}">
                <div class="mk-mcp-item-header">
                    <span class="mk-mcp-item-title-preview">New Item</span>
                    <div class="mk-mcp-item-actions">
                        <button type="button" class="mk-mcp-duplicate-item">D</button>
                        <button type="button" class="mk-mcp-delete-item">X</button>
                    </div>
                </div>
                <div class="mk-mcp-item-content">
                    <input type="text" class="mk-mcp-item-title" placeholder="Title" value="New Item">
                    <textarea class="mk-mcp-item-text" placeholder="Text"></textarea>
                </div>
            </div>`;
        itemsWrapper.append(newItem);
        serializeCalendarData();
    });

    // Delete item
    calendarContainer.on('click', '.mk-mcp-delete-item', function() {
        if (confirm('Are you sure you want to delete this item?')) {
            $(this).closest('.mk-mcp-item').remove();
            serializeCalendarData();
        }
    });
    
    // Duplicate item
    calendarContainer.on('click', '.mk-mcp-duplicate-item', function() {
        const originalItem = $(this).closest('.mk-mcp-item');
        const clonedItem = originalItem.clone();
        
        // Give new unique ID and update values to match original
        clonedItem.attr('data-id', new Date().getTime());
        clonedItem.find('.mk-mcp-item-title').val(originalItem.find('.mk-mcp-item-title').val());
        clonedItem.find('.mk-mcp-item-text').val(originalItem.find('.mk-mcp-item-text').val());
        
        originalItem.after(clonedItem);
        serializeCalendarData();
    });
    
    // Toggle item content view
    calendarContainer.on('click', '.mk-mcp-item-header', function(e) {
         if ($(e.target).is('button')) return; // Don't toggle if a button was clicked
        $(this).siblings('.mk-mcp-item-content').slideToggle(200);
    });
    
    // Update title preview on keyup
    calendarContainer.on('keyup', '.mk-mcp-item-title', function() {
        const title = $(this).val();
        $(this).closest('.mk-mcp-item').find('.mk-mcp-item-title-preview').text(title || '...');
        serializeCalendarData();
    });

    // Serialize on any input change
    calendarContainer.on('change', 'textarea', serializeCalendarData);


    // Initial load
    loadCalendarGrid();
});
