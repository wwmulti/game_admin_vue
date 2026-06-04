define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/platform/index' + location.search,
                    add_url: 'merchant/platform/add',
                    edit_url: 'merchant/platform/edit',
                    del_url: 'merchant/platform/del',
                    multi_url: 'merchant/platform/multi',
                    import_url: 'merchant/platform/import',
                    table: 'platform',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                showToggle:false,
                showColumns:false,
                showExport:false,
                search:false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'username', title: __('用户名'), operate: false},
                        {field: 'name', title: __('昵称'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {
                            field: "status",
                            title: __("状态"),
                            align: "center",
                            table: table,
                            formatter: Table.api.formatter.status,
                            searchList: { 1: __("正常"), 0: __("禁用") },
                        },
                        {field: 'createtime', title: __('创建时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('更新时间'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {
                            field: "operate",
                            title: __("Operate"),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: "platform_admin",
                                    text: __("商户列表"),
                                    title: __("商户列表"),
                                    classname: "btn btn-xs btn-primary btn-dialog",
                                    //弹窗位置，//自带参数ids
                                    url: function (row) {
                                        return 'merchant/merchant/index?platform_id=' + row.id;
                                    },
                                    //弹窗大小
                                    extend: 'data-area=\'["85%","95%"]\'',
                                    //参数回传的数据
                                    callback: function (data) {},
                                },
                            ],
                        },
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        platform_admin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'merchant/platform/platform_admin'+ location.search,
                    add_url: 'merchant/platform/platform_admin_add'+ location.search,
                    edit_url: 'merchant/platform/platform_admin_edit',
                    table: "platform_admin",
                },
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: "id",
                sortName: "id",
                columns: [
                    [
                        {field: 'username', title: __('用户名'), operate: false},
                        {field: 'name', title: __('商户名称'), operate: false},
                        { field: "token", title: "token", operate: false },
                        { field: "secret", title: "secret", operate: false },
                        {field: "operate", title: __("Operate"), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate,},
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        platform_admin_add: function () {
            Controller.api.bindevent();
        },
        platform_admin_edit: function () {
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