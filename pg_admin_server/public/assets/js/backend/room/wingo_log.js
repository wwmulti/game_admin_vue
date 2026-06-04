define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'room/wingo_log/index' + location.search,
                    table: 'room',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: false,
                fixedRightNumber: 1,
                dblClickToEdit: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                search: false,
                commonSearch: false,
                columns: [
                    [
                        {field: 'remote_uid', title: __('用户ID'), sortable: false, operate: false},
                        {
                            field: 'money', title: __('余额'), sortable: false, operate: false, table: table, buttons: [
                                {
                                    name: "detail",
                                    confirm: '是否更新用户余额？',
                                    refresh: true, //操作完成后是否刷新列表
                                    text: function (row) {
                                        return row.money+'<div>点击更新</div>';
                                    },//按钮名称
                                    classname: 'btn btn-xs btn-info btn-magic btn-ajax',
                                    icon: 'fa fa-view',
                                    extend: 'data-area=\'["70%","80%"]\'',
                                    url: function (row) {
                                        return 'room/wingo_log/update_balance?user_id=' + row.user_id;
                                    },
                                },
                            ],
                            formatter: Table.api.formatter.operate,
                        },
                        {field: 'sum_bet', title: __('总下注'), sortable: false, operate: false},
                        {field: 'red', title: __('红色'), sortable: false, operate: false},
                        {field: 'green', title: __('绿色'), sortable: false, operate: false},
                        {field: 'purple', title: __('紫色'), sortable: false, operate: false},
                        {field: 'zero', title: __('0'), sortable: false, operate: false},
                        {field: 'one', title: __('1'), sortable: false, operate: false},
                        {field: 'two', title: __('2'), sortable: false, operate: false},
                        {field: 'three', title: __('3'), sortable: false, operate: false},
                        {field: 'four', title: __('4'), sortable: false, operate: false},
                        {field: 'five', title: __('5'), sortable: false, operate: false},
                        {field: 'six', title: __('6'), sortable: false, operate: false},
                        {field: 'seven', title: __('7'), sortable: false, operate: false},
                        {field: 'eight', title: __('8'), sortable: false, operate: false},
                        {field: 'nine', title: __('9'), sortable: false, operate: false},
                        {field: 'small', title: __('小'), sortable: false, operate: false},
                        {field: 'large', title: __('大'), sortable: false, operate: false},
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