define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'room/win_realtime/index' + location.search,
                    edit_url: 'room/win_realtime/edit',
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
                        {field: 'id', title: __('id')},
                        {field: 'issue', title: __('期数')},
                        {
                            field: 'room_id',
                            title: __('房间名'),
                            searchList: $.getJSON("room/win_realtime/room_name"),
                            formatter: Table.api.formatter.normal,
                        },
                        {field: 'number_rand', title: __('默认开奖号码')},
                        {field: 'number_pre', title: __('指定开奖号码')},
                        {field: 'number', title: __('开奖号码')},
                        {
                            field: "status",
                            title: __("状态"),
                            align: "center",
                            table: table,
                            formatter: Table.api.formatter.status,
                            searchList: { 1: __("已开奖"), 0: __("未开奖") },
                        },
                        {field: 'draw_time_pre', title: __('预计开奖时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'draw_time', title: __('实际开奖时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'operate', title: __('Operate'),
                            table: table,
                            buttons: [
                                {
                                    name: "edit",
                                    title: "编辑",//按钮名称
                                    classname: 'btn btn-xs btn-success btn-magic btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: function(row){
                                        return 'room/win_realtime/edit/ids/'+row.id;
                                    },
                                },
                                {
                                    name: "detail",
                                    text: "查看详情",//按钮名称
                                    classname: 'btn btn-xs btn-warning btn-magic btn-dialog',
                                    icon: 'fa fa-view',
                                    extend: 'data-area=\'["80%","85%"]\'',
                                    url: function(row){
                                        return 'room/wingo_log/index?issue='+row.issue+"&room_id="+row.room_ids;
                                    },
                                },
                                {
                                    name: "statistics_detail",
                                    text: "统计详情",//按钮名称
                                    classname: 'btn btn-xs btn-info btn-magic btn-dialog',
                                    icon: 'fa fa-view',
                                    extend: 'data-area=\'["80%","85%"]\'',
                                    url: function(row){
                                        return 'room/wingo_statistics_detail/index?issue='+row.issue+"&room_id="+row.room_ids;
                                    },
                                    visible: function (row) {
                                        return row.status===1;
                                    },

                                },
                            ],
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                        }
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
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
