
$(document).ready(function () {

    function updateTheme(input) {
        const value = $(input).val();
        const wrapper = $(input).closest(".kot-input-box");

        wrapper.find(".kot-color-preview").css("background", value);

        wrapper.find('input[type="text"]').val(value);
    }

    $("#primaryColor").on("input", function () {
        updateTheme(this);
        document.documentElement.style.setProperty("--dynamic-primary", $(this).val());
    });

    $("#secondaryColor").on("input", function () {
        updateTheme(this);
        document.documentElement.style.setProperty("--dynamic-secondary", $(this).val());
    });

    $("#sidebarColor").on("input", function () {
        updateTheme(this);
        document.documentElement.style.setProperty("--dynamic-sidebar", $(this).val());
    });

    $("#accentColor").on("input", function () {
        updateTheme(this);
        document.documentElement.style.setProperty("--dynamic-accent", $(this).val());
    });

});