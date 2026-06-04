define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'room/win_room/index' + location.search,
                    add_url: 'room/win_room/add',
                    edit_url: 'room/win_room/edit',
                    del_url: 'room/win_room/del',
                    multi_url: 'room/win_room/multi',
                    import_url: 'room/win_room/import',
                    table: 'wingo_room',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('id')},
                        {field: 'seconds', title: __('Seconds')},
                        {field: 'stop_second', title: __('Stop_second')},
                        // {field: 'settle_second', title: __('Settle_second')},
                        {field: 'odds', title: __('计算开奖概率(%)')},
                        {field: 'weight', title: __('排序')},
                        {
                            field: "status",
                            title: __("Status"),
                            align: "center",
                            table: table,
                            formatter: Table.api.formatter.toggle,
                            searchList: { 1: __("开启"), 0: __("禁用") },
                            sortable: true
                        },
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
