function initDoc(options) {
    // init DataTable
    if ('datatables' in options && options.datatables.length > 0) {
        options.datatables.map((datatableID) => {
            $j('#' + datatableID).DataTable({
                "aoColumnDefs": [
                    { "aTargets": [2, 4, 5], "sType": "date-eu" },
                    { "aTargets": [-1, -2], "sortable": false },
                ],
                'oLanguage':
                {
                    'sUrl': HTTP_ROOT_DIR + '/js/include/jquery/dataTables/dataTablesLang.php'
                }
            });
        });
    }

    $j('.subscribe-group').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if ('loadModuleJS' in options && options.loadModuleJS.length > 0) {
            options.loadModuleJS.map((params) => {
                const nsClass = params.className.split(".");
                const c = new window[nsClass[0]][nsClass[1]](params.baseUrl);
                c.setCourseId($j(this).data('courseid'));
                c.setInstanceId($j(this).data('instanceid'));
                c.subscribeGroup();
            });
        }

    });
}
