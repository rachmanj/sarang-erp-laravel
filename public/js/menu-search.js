/**
 * Menu Search Component
 * Provides autocomplete search functionality for menu items
 */
(function ($) {
    "use strict";

    let searchTimeout;
    let allMenuItems = [];
    let filteredItems = [];
    let selectedIndex = -1;
    let $searchInput;
    let $searchResults;
    let isInitialized = false;

    /**
     * Initialize menu search
     */
    function init() {
        if (isInitialized) {
            return;
        }

        $searchInput = $("#menu-search-input");
        $searchResults = $("#menu-search-results");

        if (!$searchInput.length || !$searchResults.length) {
            return;
        }

        // Load menu items on initialization
        loadMenuItems();

        // Handle input events
        $searchInput.on("input", handleInput);
        $searchInput.on("keydown", handleKeydown);
        $searchInput.on("focus", handleFocus);
        $searchInput.on("blur", handleBlur);

        // Handle result clicks
        $searchResults.on("click", ".menu-search-item", handleItemClick);

        // Close on outside click
        $(document).on("click", function (e) {
            if (!$(e.target).closest("#menu-search-container").length) {
                hideResults();
            }
        });

        isInitialized = true;
    }

    /**
     * Load menu items from API
     */
    function loadMenuItems() {
        $.ajax({
            url: "/api/menu/search",
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
            success: function (response) {
                if (response.items) {
                    allMenuItems = response.items;
                }
            },
            error: function (xhr) {
                console.error("Failed to load menu items:", xhr);
            },
        });
    }

    /**
     * Handle input event
     */
    function handleInput(e) {
        const query = $(e.target).val().trim();

        // Clear previous timeout
        clearTimeout(searchTimeout);

        if (query.length === 0) {
            hideResults();
            return;
        }

        // Debounce search
        searchTimeout = setTimeout(function () {
            searchMenuItems(query);
        }, 300);
    }

    /**
     * Search menu items
     */
    function searchMenuItems(query) {
        if (!query || query.length === 0) {
            hideResults();
            return;
        }

        const searchTerm = query.toLowerCase();
        filteredItems = allMenuItems.filter(function (item) {
            return item.searchText.indexOf(searchTerm) !== -1;
        });

        // Sort by relevance
        filteredItems.sort(function (a, b) {
            const aTitleMatch =
                a.title.toLowerCase().indexOf(searchTerm) !== -1;
            const bTitleMatch =
                b.title.toLowerCase().indexOf(searchTerm) !== -1;

            if (aTitleMatch && !bTitleMatch) return -1;
            if (!aTitleMatch && bTitleMatch) return 1;

            return a.title.localeCompare(b.title);
        });

        // Limit to 15 results
        filteredItems = filteredItems.slice(0, 15);

        displayResults();
    }

    /**
     * Display search results
     */
    function displayResults() {
        if (filteredItems.length === 0) {
            $searchResults.html(
                '<div class="menu-search-no-results">No results found</div>'
            );
            $searchResults.show();
            return;
        }

        let html = "";
        filteredItems.forEach(function (item, index) {
            const isSelected = index === selectedIndex ? "active" : "";
            html += `
                <div class="menu-search-item ${isSelected}" data-index="${index}" data-route="${
                item.route
            }">
                    <i class="${item.icon} mr-2"></i>
                    <div class="menu-search-item-content">
                        <div class="menu-search-item-title">${highlightMatch(
                            item.title,
                            $searchInput.val()
                        )}</div>
                        <div class="menu-search-item-breadcrumb">${
                            item.breadcrumb
                        }</div>
                    </div>
                </div>
            `;
        });

        $searchResults.html(html);
        $searchResults.show();
        selectedIndex = -1;
    }

    /**
     * Highlight matching text
     */
    function highlightMatch(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query})`, "gi");
        return text.replace(regex, "<strong>$1</strong>");
    }

    /**
     * Handle keyboard navigation
     */
    function handleKeydown(e) {
        if (!$searchResults.is(":visible") || filteredItems.length === 0) {
            if (e.key === "Enter") {
                e.preventDefault();
                return;
            }
            return;
        }

        switch (e.key) {
            case "ArrowDown":
                e.preventDefault();
                selectedIndex = Math.min(
                    selectedIndex + 1,
                    filteredItems.length - 1
                );
                updateSelection();
                break;
            case "ArrowUp":
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateSelection();
                break;
            case "Enter":
                e.preventDefault();
                if (
                    selectedIndex >= 0 &&
                    selectedIndex < filteredItems.length
                ) {
                    navigateToItem(filteredItems[selectedIndex]);
                } else if (filteredItems.length > 0) {
                    navigateToItem(filteredItems[0]);
                }
                break;
            case "Escape":
                e.preventDefault();
                hideResults();
                $searchInput.blur();
                break;
        }
    }

    /**
     * Update selection highlight
     */
    function updateSelection() {
        $searchResults.find(".menu-search-item").removeClass("active");
        if (selectedIndex >= 0) {
            $searchResults
                .find(`[data-index="${selectedIndex}"]`)
                .addClass("active");
            // Scroll into view
            const $selected = $searchResults.find(
                `[data-index="${selectedIndex}"]`
            );
            if ($selected.length) {
                $selected[0].scrollIntoView({
                    block: "nearest",
                    behavior: "smooth",
                });
            }
        }
    }

    /**
     * Handle focus event
     */
    function handleFocus(e) {
        const query = $(e.target).val().trim();
        if (query.length > 0 && filteredItems.length > 0) {
            $searchResults.show();
        }
    }

    /**
     * Handle blur event
     */
    function handleBlur(e) {
        // Delay to allow click events to fire
        setTimeout(function () {
            hideResults();
        }, 200);
    }

    /**
     * Handle item click
     */
    function handleItemClick(e) {
        e.preventDefault();
        const $item = $(e.currentTarget);
        const route = $item.data("route");
        if (route) {
            navigateToItem({ route: route });
        }
    }

    /**
     * Navigate to menu item
     */
    function navigateToItem(item) {
        if (item && item.route) {
            window.location.href = item.route;
        }
    }

    /**
     * Hide search results
     */
    function hideResults() {
        $searchResults.hide();
        selectedIndex = -1;
    }

    // Initialize on document ready
    $(document).ready(function () {
        init();
    });

    // Keyboard shortcut: Ctrl+K or Cmd+K to focus search
    $(document).on("keydown", function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === "k") {
            e.preventDefault();
            const $input = $("#menu-search-input");
            if ($input.length) {
                $input.focus();
            }
        }
    });
})(jQuery);
