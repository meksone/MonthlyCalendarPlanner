/**
 * Admin script for Monthly Calendar Planner
 * @version 1.0.7
 */
jQuery(document).ready(function($) {
    const builderContainer = $('#mk-mcp-builder-wrapper');
    const itemsJsonInput = $('#mk_mcp_calendar_items_json');
    const viewModeSelect = $('#mk_mcp_view_mode');
    const tableSettings = $('#mk-mcp-table-settings');
    const columnCountSelect = $('#mk_mcp_column_count');
    const columnNameInputs = $('.mk-mcp-column-name-input');
    const templateSidebar = $('#mk-mcp-templates-sidebar');

    const modalOverlay = $('#mk-mcp-view-switcher-modal-overlay');
    let previousViewMode = viewModeSelect.val();

    function loadBuilderView() {
        // Update view mode state
        previousViewMode = viewModeSelect.val();
        
        // Toggle settings visibility
        if (viewModeSelect.val() === 'table') {
            tableSettings.removeClass('settings-hidden');
            handleColumnNameInputs();
        } else {
            tableSettings.addClass('settings-hidden');
        }

        const month = $('#mk_mcp_month').val();
        const year = $('#mk_mcp_year').val();
        const view_mode = viewModeSelect.val();
        const items = itemsJsonInput.val();
        let ajaxData = { action: 'mk_mcp_get_admin_builder_view', nonce: mk_mcp_ajax.nonce, month, year, view_mode, items };
        if (view_mode === 'table') {
            ajaxData.column_count = columnCountSelect.val();
            ajaxData.column_names = JSON.stringify(columnNameInputs.map(function() { return $(this).val(); }).get());
        }
        builderContainer.html('<div class="mk-mcp-loader"><p>'+mk_mcp_ajax.i18n.loading+'</p></div>');
        $.post(mk_mcp_ajax.ajax_url, ajaxData, function(response) {
            if (response.success) {
                builderContainer.html(response.data);
                initializeSortableAndDraggable();
            } else {
                builderContainer.html('<p>Error loading view.</p>');
            }
        });
    }

    function serializeData() {
        const data = {};
        $('.mk-mcp-day-items-wrapper').each(function() {
            const day = $(this).data('day'), col = $(this).data('col');
            if (day === undefined || col === undefined) return;
            if (!data[day]) data[day] = {};
            const items = [];
            $(this).find('.mk-mcp-item').each(function() {
                items.push({ title: $(this).find('.mk-mcp-item-title').val(), text: $(this).find('.mk-mcp-item-text').val() });
            });
            if (items.length > 0) data[day][col] = items;
        });
        itemsJsonInput.val(JSON.stringify(data));
    }

    function initializeSortableAndDraggable() {
        $('.mk-mcp-day-items-wrapper').sortable({
            connectWith: ".mk-mcp-day-items-wrapper",
            placeholder: "mk-mcp-item-placeholder",
            revert: true,
            update: serializeData,
            receive: function(event, ui) {
                const template = ui.sender;
                const newItem = $(this).find('.mk-mcp-template-item');
                const title = template.data('item-title');
                const text = template.data('item-text');
                newItem.replaceWith(getNewItemHtml(title, text));
                serializeData();
            }
        }).disableSelection();
        templateSidebar.find('.mk-mcp-template-item').draggable({
            connectToSortable: ".mk-mcp-day-items-wrapper",
            helper: "clone",
            revert: "invalid"
        });
    }
    
    const generateUniqueId = () => 'item-' + Date.now().toString(36) + Math.random().toString(36).substr(2, 9);

    const getNewItemHtml = (title = "New Item", text = "") => {
        const escTitle = $('<div/>').text(title).html();
        const escText = $('<div/>').text(text).html();
        return `
            <div class="mk-mcp-item" data-id="${generateUniqueId()}">
                <div class="mk-mcp-item-header">
                    <span class="mk-mcp-item-title-preview">${escTitle}</span>
                    <div class="mk-mcp-item-actions"><button type="button" class="mk-mcp-duplicate-item">D</button><button type="button" class="mk-mcp-delete-item">X</button></div>
                </div>
                <div class="mk-mcp-item-content" style="display: block;"><input type="text" class="mk-mcp-item-title" placeholder="Title" value="${escTitle}"><textarea class="mk-mcp-item-text" placeholder="Text">${escText}</textarea></div>
            </div>`;
    };
    
    function handleColumnNameInputs() {
        const count = parseInt(columnCountSelect.val(), 10);
        columnNameInputs.each(function(index) {
            $(this).toggle(index < count);
        });
    }

    // --- Event Handlers ---
    $('#mk_mcp_month, #mk_mcp_year, #mk_mcp_column_count').on('change', function() { serializeData(); loadBuilderView(); });
    columnNameInputs.on('change keyup', loadBuilderView);

    viewModeSelect.on('focus', function() {
        previousViewMode = $(this).val();
    }).on('change', function() {
        serializeData(); // Save data before showing confirm
        modalOverlay.css('display', 'flex'); // Correctly use flex to center the modal
    });

    $('#mk-mcp-confirm-switch').on('click', function() {
        modalOverlay.hide();
        loadBuilderView();
    });

    $('#mk-mcp-cancel-switch').on('click', function() {
        modalOverlay.hide();
        viewModeSelect.val(previousViewMode); // Revert dropdown
    });

    builderContainer.on('click', '.mk-mcp-add-item-btn', function() { $(this).siblings('.mk-mcp-day-items-wrapper').append(getNewItemHtml()); serializeData(); });
    builderContainer.on('click', '.mk-mcp-add-item-btn-table', function() { $(this).before(getNewItemHtml()); serializeData(); });
    builderContainer.on('click', '.mk-mcp-delete-item', function() { if (confirm(mk_mcp_ajax.i18n.delete_confirm)) { $(this).closest('.mk-mcp-item').remove(); serializeData(); } });
    builderContainer.on('click', '.mk-mcp-duplicate-item', function() { const o = $(this).closest('.mk-mcp-item'); const c = o.clone(); c.attr('data-id', generateUniqueId()); o.after(c); serializeData(); });
    builderContainer.on('click', '.mk-mcp-item-header', function(e) { if (!$(e.target).is('button')) $(this).siblings('.mk-mcp-item-content').slideToggle(200); });
    builderContainer.on('keyup change', '.mk-mcp-item-title, .mk-mcp-item-text', function(){
        if($(this).hasClass('mk-mcp-item-title')){
            $(this).closest('.mk-mcp-item').find('.mk-mcp-item-title-preview').text($(this).val() || '...');
        }
        serializeData();
    });

    // Initial load
    loadBuilderView();
});

