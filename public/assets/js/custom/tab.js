$(document).ready(function () {

    function showTab(tabId, save = true) {
        $(".tab-item").removeClass("active");
        $(".tab-content").removeClass("active");

        $(`[data-tab="${tabId}"]`).addClass("active");
        $(`#${tabId}`).addClass("active");

        if (save) {
            localStorage.setItem("activeTab", tabId);
        }
    }

    $(document).on("click", ".tab-item", function () {
        const tabId = $(this).data("tab");
        showTab(tabId, true);
    });

    const savedTab = localStorage.getItem("activeTab");

    if (savedTab && $(`#${savedTab}`).length) {
        showTab(savedTab, false);
    } else {
        // active from html 
    }

});
