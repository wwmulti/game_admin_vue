define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/details/index' + location.search,
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
                        // {field: 'user_id', title: __('用户ID'),operate: false},
                        {field: 'sid', title: __('订单ID'), operate: false,sortable: false},
                        {
                            field: 'room_id',
                            title: __('所属游戏'),
                            addClass: "selectpage",
                            extend: "data-source='user/details/room_name' data-field='name' data-primary-key='id'",
                            defaultValue:roomdefaultValue
                        },
                        {field: 'bet', title: __('下注金额'), operate: false,sortable: false},
                        {field: 'transfer', title: __('净输赢'), operate: false,sortable: false},
                        {field: 'created_at',defaultValue:created_atdefaultValue, title: __('创建时间'),  operate: 'RANGE', addclass:'datetimerange',sortable: true, autocomplete:false, formatter: Table.api.formatter.datetime},
                    ]
                ]
            });
            $(document).on("click", ".sortable", function () {
                var index = layer.msg('加载中', {icon: 16, shade: 0.3, time:0});
                $(document).ajaxStop(function() {
                    // 表格数据加载完成
                    Layer.close(index);
                });
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