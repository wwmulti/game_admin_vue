define(["jquery", "bootstrap", "backend", "table", "form"], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "ttpg/ttpg_statistics/index" + location.search,
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
                showColumns: false,
                search: false,
                onLoadSuccess:function (data){
                    table.bootstrapTable('hideColumn',data.current_permission===2?'':'merchant_id');//隐藏行
                    table.bootstrapTable('hideColumn',data.current_permission===2?'':'platform_id');//隐藏行
                    // table.bootstrapTable('hideColumn',data.current_permission===1?'':'name');//隐藏行
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'date_time', title: __('日期'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {
                            field: 'room_id',
                            title: __('所属游戏'),
                            addClass: "selectpage",
                            extend: "data-source='ttpg/ttpg_statistics/room_name' data-field='name'"
                        },
                        // {
                        //     field: "platform_id",
                        //     title: __("平台"),
                        //     searchList: $.getJSON("statistics/supplier/platform_name"),
                        //     formatter: Table.api.formatter.normal,
                        // },
                        {
                            field: "merchant_id",
                            title: __("商户"),
                            searchList: $.getJSON("statistics/supplier/merchant_name"),
                            formatter: Table.api.formatter.normal,
                        },
                        {field: "bet_amount", title: "下注总金额", operate: false},
                        {field: "user_number", title: "参与人数", operate: false},
                        {field: "bet_times", title: "押注次数", operate: false},
                        {field: "win_amount", title: "赢金", operate: false},
                        // {field: "transfer", title: "玩家净输赢", operate: false},
                        {field: "win_or_loss", title: "玩法营收", operate: false},
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