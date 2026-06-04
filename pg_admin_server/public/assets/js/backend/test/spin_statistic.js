define(["jquery", "bootstrap", "backend", "table", "form"], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "test/spin_statistic/index" + location.search,
                    post_url: "test/spin_statistic/post",
                    table: "merchant",
                },
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: "created_at",
                sortName: "created_at",
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
                        {
                            field: 'room_name',
                            title: __('所属游戏'),
                            searchList: $.getJSON("test/spin_statistic/room_name"),
                        },
                        {field: 'rtp', title: __("档位")},
                        {field: 'mod', title: __("模式"), searchList: {'0': '普通','2': '必中'}, formatter: Table.api.formatter.status},
                        {field: "bet_amount", title: "下注金额", operate: 'BETWEEN'},
                        {field: "bet_time", title: "下注次数", operate: 'BETWEEN'},
                        {field: "bet_total", title: "押注总额", operate: 'BETWEEN'},
                        {field: "win_total", title: "总赢金", operate: 'BETWEEN'},
                        {field: "win_times", title: "总赢次数", operate: false},
                        {field: "win_per", title: "中奖概率", operate: false},
                        {field: "rebate_per", title: "返奖率", operate: false},
                        {field: "spe_times", title: "特殊游戏次数", operate: 'BETWEEN'},
                        {field: "spe_total", title: "特殊游戏中奖总额", operate: 'BETWEEN'},
                        {field: "special_trigger_probability", title: "特殊游戏触发概率", operate: false},
                        {field: "free_times", title: "免费游戏次数", operate: 'BETWEEN'},
                        {field: "free_win", title: "免费游戏赢金", operate: 'BETWEEN'},
                        {field: "free_trigger_probability", title: "免费游戏触发概率", operate: false},
                        {field: 'created_at', title: __('创建时间'),  operate: 'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table,
                            buttons: [
                                {
                                    name: "detail",
                                    confirm: '确认要再来一次？',
                                    refresh: true, //操作完成后是否刷新列表
                                    text: '再来一次',
                                    classname: 'btn btn-xs btn-info btn-magic btn-ajax',
                                    icon: 'fa fa-view',
                                    url: function (row) {
                                        return 'test/spin_statistic/once_more?id=' + row.id;
                                    },
                                },
                            ],
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                        }
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
        },

        imitate: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    /* index_url: "test/spin_statistic/imitate",
                    post_url: "test/spin_statistic/post", */
                    table: "merchant",
                    imitate_url: "test/spin_statistic/imitate",
                    imitate_post_url: "test/spin_statistic/imitatePost",
                },
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.imitate_url,
                pk: "id",
                sortName: "c_time",
                fixedColumns: true,
                fixedNumber: 5,
                // fixedRightNumber: 1,
                dblClickToEdit: false,
                showToggle: false,
                showColumns: false,
                search: false,
                /* onLoadSuccess: function (data) {
                    table.bootstrapTable('hideColumn', data.current_permission === 2 ? '' : 'merchant_id');//隐藏行
                    table.bootstrapTable('hideColumn', data.current_permission === 2 ? '' : 'platform_id');//隐藏行
                    // table.bootstrapTable('hideColumn',data.current_permission===1?'':'name');//隐藏行
                }, */
                columns: [
                    [
                        {
                            field: 'game_code',
                            title: __('所属游戏'),
                            searchList: $.getJSON("test/spin_statistic/room_name"),
                        },
                        {
                            field: 'type_id',
                            title: __('游戏类型'),
                            searchList: $.getJSON("test/spin_statistic/room_name"),
                            operate: false
                        },
                        { field: 'rtp', title: __("RTP"), operate: false },
                        { field: 'nums', title: __("设置局数"), operate: false },
                        { field: 'sus', title: __("实际局数"), operate: false },
                        { field: 'win', title: __("赢局"), operate: false },
                        { field: 'lose', title: __("输局"), operate: false },
                        { field: '0', title: __("0倍"), operate: false },
                        { field: '0~1', title: __("0~1倍"), operate: false },
                        { field: '1~2', title: __("1~2倍"), operate: false },
                        { field: '2~3', title: __("2~3倍"), operate: false },
                        { field: '3~4', title: __("3~4倍"), operate: false },
                        { field: '4~5', title: __("4~5倍"), operate: false },
                        { field: '5~10', title: __("5~10倍"), operate: false },
                        { field: '10~15', title: __("10~15倍"), operate: false },
                        { field: '15~20', title: __("15~20倍"), operate: false },
                        { field: '20~25', title: __("20~25倍"), operate: false },
                        { field: '25~30', title: __("25~30倍"), operate: false },
                        { field: '30~40', title: __("30~40倍"), operate: false },
                        { field: '40~50', title: __("40~50倍"), operate: false },
                        { field: '50~75', title: __("50~75倍"), operate: false },
                        { field: '75~100', title: __("75~100倍"), operate: false },
                        { field: '>100', title: __(">100倍"), operate: false },
                        { field: 'c_time', title: __('时间'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        {
                            field: 'operate',
                            title: __('operate'), 
                            table: table,
                            buttons: [
                                {
                                    name: "detail",
                                    text: __('表格展示'),
                                    title: __('获取结果'),
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-list',
                                    extend: 'data-area=["950px","950px"]',
                                    url: function (row) {
                                        return 'test/spin_statistic/getResult?id=' + row.id;
                                    },

                                },
                            ],
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                        }
                    ],
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
    };
    return Controller;
});