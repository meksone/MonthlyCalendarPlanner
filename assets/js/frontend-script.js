/**
 * Frontend script for Monthly Calendar Planner Live Search
 * @version 1.1.0
 */
document.addEventListener('DOMContentLoaded', function() {
    const searchInputs = document.querySelectorAll('.mk-mcp-search-input');

    searchInputs.forEach(function(searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const calendarWrapper = e.target.closest('.mk-mcp-frontend-calendar-wrapper');

            if (calendarWrapper) {
                const items = calendarWrapper.querySelectorAll('.mk-mcp-item');

                items.forEach(function(item) {
                    const titleElement = item.querySelector('.mk-mcp-item-title');
                    const textElement = item.querySelector('.mk-mcp-item-text');

                    const title = titleElement ? titleElement.textContent.toLowerCase() : '';
                    const text = textElement ? textElement.textContent.toLowerCase() : '';

                    if (searchTerm.length > 0 && (title.includes(searchTerm) || text.includes(searchTerm))) {
                        item.classList.add('highlighted');
                    } else {
                        item.classList.remove('highlighted');
                    }
                });
            }
        });
    });
});
