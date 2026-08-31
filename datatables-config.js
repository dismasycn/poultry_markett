// Global DataTables default config
$.extend($.fn.dataTable.defaults, {
    responsive: true,
    language: { search: "Search:", lengthMenu: "Show _MENU_ entries" },
    dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>t<"row"<"col-sm-6"i><"col-sm-6"p>>'
});