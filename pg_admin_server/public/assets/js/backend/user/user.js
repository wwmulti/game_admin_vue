define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index' + location.search,
                    // add_url: 'user/user/add',
                    // edit_url: 'user/user/edit',
                    // del_url: 'user/user/del',
                    // multi_url: 'user/user/multi',
                    // import_url: 'user/user/import',
                    table: 'users',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                dblClickToEdit: false,
                showToggle: false,
                showColumns: false,
                search: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: "platform_id", title: __("平台"), searchList: $.getJSON("user/user/platform_name"),sortable: true,},
                        {field: "merchant_id", title: __("所属商户"), searchList: $.getJSON("user/user/merchant_name"),sortable: true,},
                        {field: 'remote_uid', title: __('Remote_uid'), operate: 'LIKE',sortable: true,},
                        {field: 'rtp', title: __('Rtp')},
                        {
                            field: 'money', title: __('余额'), sortable: true, table: table, buttons: [
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
                                        return 'user/user/update_balance?user_id=' + row.id;
                                    },
                                },
                            ],
                            formatter: Table.api.formatter.operate,
                            operate:'BETWEEN'
                        },
                        {field: 'name', title: __('Name'), operate: 'LIKE',sortable: true,},
                        {field: 'created_at', title: __('Created_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false,sortable: true,},
                        {field: 'operate', title: __('Operate'), table: table,
                            buttons: [
                                {
                                    name: "details",
                                    text: "游戏详情",//按钮名称
                                    title: function (row) {
                                        return row.remote_uid+':游戏详情';
                                    },
                                    classname: 'btn btn-xs btn-info btn-magic btn-dialog',
                                    icon: 'fa fa-newspaper-o',
                                    //弹窗大小
                                    extend: 'data-area=\'["80%","90%"]\'',
                                    url: function (row) {
                                        return 'user/details/index?ids='+row.id;
                                    },
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                    },
                                    error: function (data, ret) {
                                        return false;
                                    },
                                },
                            ],
                            events: Table.api.events.operate, formatter: function (value, row, index) {
                                var that = $.extend({}, this);
                                $(table).data("operate-edit", null); // 列表页面隐藏 .编辑operate-edit  - 删除按钮operate-del
                                $(table).data("operate-del", null); // 列表页面隐藏 .编辑operate-edit  - 删除按钮operate-del
                                that.table = table;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            }
                        }
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