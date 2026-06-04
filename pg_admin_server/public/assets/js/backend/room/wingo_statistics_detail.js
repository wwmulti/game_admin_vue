define(["jquery", "bootstrap", "backend", "table", "form"], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "room/wingo_statistics_detail/index" + location.search,
                    table: "merchant",
                },
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: "date_time",
                sortName: "date_time",
                fixedColumns: true,
                fixedRightNumber: 1,
                dblClickToEdit: false,
                showToggle: false,
                pagination: false,
                showColumns: false,
                commonSearch: true,
                showExport: false,
                search: false,
                columns: [
                    [
                        {
                            field: 'merchant_id',
                            title: __('所属商户'),
                            addClass: "selectpage",
                            extend: "data-source='room/wingo_statistics_detail/merchant_name' data-field='name' data-primary-key='id'",
                            // defaultValue:merchantdefaultValue
                        },
                        {field: "count", title: "订单数", operate: false},
                        {field: "user_number", title: "人次", operate: false},
                        {field: "bet", title: "下注金额", operate: false},
                        {field: "transfer", title: "玩家输赢", operate: false},
                    ],
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});