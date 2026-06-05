define(["jquery", "bootstrap", "backend", "table", "form"], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "merchant/setting/index" + location.search,
                    add_url: "merchant/setting/add",
                    edit_url: "merchant/setting/edit",
                    del_url: "merchant/setting/del",
                    table: "setting",
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