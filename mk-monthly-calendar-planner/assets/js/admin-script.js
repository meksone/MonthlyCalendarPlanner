jQuery(document).ready(function($) {
    const calendarContainer = $('#mcp-calendar-grid-wrapper');
    const itemsJsonInput = $('#mcp_calendar_items_json');

    // Function to fetch and render the calendar grid
    function loadCalendarGrid() {
        const month = $('#mcp_month').val();
        const year = $('#mcp_year').val();
        const items = itemsJsonInput.val();
        
        calendarContainer.html('<div class="mcp-loader"><p>Loading Calendar...</p></div>');

        $.post(mcp_ajax.ajax_url, {
            action: 'mcp_get_admin_calendar_grid',
            nonce: mcp_ajax.nonce,
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
        $('.mcp-day').each(function() {
            const day = $(this).data('day');
            if (day) {
                const items = [];
                $(this).find('.mcp-item').each(function() {
                    const title = $(this).find('.mcp-item-title').val();
                    const text = $(this).find('.mcp-item-text').val();
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
        $('.mcp-day-items-wrapper').sortable({
            connectWith: ".mcp-day-items-wrapper",
            placeholder: "mcp-item-placeholder",
            revert: true,
            update: function(event, ui) {
                serializeCalendarData();
            }
        }).disableSelection();
    }

    // Event handlers
    $('#mcp_month, #mcp_year').on('change', function() {
        serializeCalendarData(); // Save current state before reloading
        loadCalendarGrid();
    });

    // Add new item
    calendarContainer.on('click', '.mcp-add-item-btn', function() {
        const itemsWrapper = $(this).siblings('.mcp-day-items-wrapper');
        const newItem = `
            <div class="mcp-item" data-id="${new Date().getTime()}">
                <div class="mcp-item-header">
                    <span class="mcp-item-title-preview">New Item</span>
                    <div class="mcp-item-actions">
                        <button type="button" class="mcp-duplicate-item">D</button>
                        <button type="button" class="mcp-delete-item">X</button>
                    </div>
                </div>
                <div class="mcp-item-content">
                    <input type="text" class="mcp-item-title" placeholder="Title" value="New Item">
                    <textarea class="mcp-item-text" placeholder="Text"></textarea>
                </div>
            </div>`;
        itemsWrapper.append(newItem);
        serializeCalendarData();
    });

    // Delete item
    calendarContainer.on('click', '.mcp-delete-item', function() {
        if (confirm('Are you sure you want to delete this item?')) {
            $(this).closest('.mcp-item').remove();
            serializeCalendarData();
        }
    });
    
    // Duplicate item
    calendarContainer.on('click', '.mcp-duplicate-item', function() {
        const originalItem = $(this).closest('.mcp-item');
        const clonedItem = originalItem.clone();
        
        // Give new unique ID and update values to match original
        clonedItem.attr('data-id', new Date().getTime());
        clonedItem.find('.mcp-item-title').val(originalItem.find('.mcp-item-title').val());
        clonedItem.find('.mcp-item-text').val(originalItem.find('.mcp-item-text').val());
        
        originalItem.after(clonedItem);
        serializeCalendarData();
    });
    
    // Toggle item content view
    calendarContainer.on('click', '.mcp-item-header', function(e) {
         if ($(e.target).is('button')) return; // Don't toggle if a button was clicked
        $(this).siblings('.mcp-item-content').slideToggle(200);
    });
    
    // Update title preview on keyup
    calendarContainer.on('keyup', '.mcp-item-title', function() {
        const title = $(this).val();
        $(this).closest('.mcp-item').find('.mcp-item-title-preview').text(title || '...');
        serializeCalendarData();
    });

    // Serialize on any input change
    calendarContainer.on('change', 'textarea', serializeCalendarData);


    // Initial load
    loadCalendarGrid();
});
