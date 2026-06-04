define(["jquery", "bootstrap", "backend", "table", "form"], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "merchant/merchant/index" + location.search,
                    add_url: "merchant/merchant/add",
                    edit_url: "merchant/merchant/edit",
                    del_url: "merchant/merchant/del",
                    table: "merchant",
                },
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: "id",
                sortName: "id",
                fixedColumns: true,
                fixedRightNumber: 1,
                dblClickToEdit: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                search: false,
                onLoadSuccess: function (data) {
                    table.bootstrapTable('hideColumn', data.current_permission === 1 ? '' : 'mark');//隐藏行
                    // table.bootstrapTable('hideColumn',data.current_permission===1?'':'name');//隐藏行
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: "id", title: "ID"},
                        {field: "username", title: "用户名", operate: false},
                        {field: "name", title: __("渠道名称"), operate: "LIKE"},
                        {field: "token", title: "token", operate: false},
                        {field: "secret", title: "secret", operate: false},
                        {field: "mark", title: "备注", operate: false},
                        {
                            field: "operate",
                            title: __("Operate"),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: "edit",
                                    text: __(""),
                                    title: __("编辑"),
                                    icon:'fa fa-pencil',
                                    extend: 'data-area=\'["80%","95%"]\'',
                                    classname: "btn btn-xs btn-success  btn-dialog",
                                    //弹窗位置，//自带参数ids
                                    url: function (row) {
                                        return 'merchant/merchant/edit/ids/'+row.id;
                                    },
                                    //参数回传的数据
                                    callback: function (data) {},
                                },
                            ],
                        },
                    ],
                ],
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