jQuery(document).ready(function ($) {
    function updateURL(tabSlug) {
        const url = new URL(window.location);
        url.searchParams.set("tab", tabSlug);
        history.pushState(null, "", url);
    }

    function openTabFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get("tab");
        if (activeTab) {
            $(".acf-tab-group .acf-tab-button").each(function () {
                if ($(this).text().trim() === activeTab) {
                    $(this).trigger("click");
                }
            });
        }
    }

    $(".acf-tab-group .acf-tab-button").on("click", function () {
        const tabSlug = $(this).text().trim();
        updateURL(tabSlug);
    });

    openTabFromURL();
});
