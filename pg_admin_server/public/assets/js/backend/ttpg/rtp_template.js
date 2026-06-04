define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ttpg/rtp_template/index' + location.search,
                    add_url: 'ttpg/rtp_template/add'+ location.search,
                    edit_url: 'ttpg/rtp_template/edit',
                    del_url: 'ttpg/rtp_template/del',
                    multi_url: 'ttpg/rtp_template/multi',
                    import_url: 'ttpg/rtp_template/import',
                    table: 'template',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                dblClickToEdit: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                search: false,
                commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'room_id', title: __('所属游戏'),sortable: true,operate:false},
                        {field: 'name', title: __('编号'),sortable: true,operate:false},
                        {field: 'group_ids', title: __('分组ID'),sortable: true,operate:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});