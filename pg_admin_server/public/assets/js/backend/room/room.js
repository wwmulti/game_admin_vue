define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'room/room/index' + location.search,
                    add_url: 'room/room/add',
                    edit_url: 'room/room/edit',
                    del_url: 'room/room/del',
                    multi_url: 'room/room/multi',
                    import_url: 'room/room/import',
                    api_post_url: 'room/room/api_post',
                    batch_updates_url: 'room/room/batch_updates',
                    table: 'room',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                fixedColumns: true,
                fixedRightNumber: 1,
                dblClickToEdit: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                search: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),sortable: true},
                        {field: 'name', title: __('Name'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content,sortable: true},
                        {
                            field: "type_id",
                            title: __("分类"),
                            searchList: $.getJSON("room/room/type_name"),
                            formatter: Table.api.formatter.normal,sortable: true,
                        },
                        {
                            field: "status",
                            title: __("Status"),
                            align: "center",
                            table: table,
                            formatter: Table.api.formatter.toggle,
                            searchList: { 1: __("开启"), 0: __("禁用") },
                            sortable: true
                        },     {
                            field: "reward_status",
                            title: __("是否返奖"),
                            align: "center",
                            table: table,
                            formatter: Table.api.formatter.toggle,
                            searchList: { 1: __("开启"), 0: __("禁用") },
                            sortable: true
                        },
                        {
                            field: "type",
                            title: __("游戏类型"),
                            align: "center",
                            table: table,
                            formatter: Table.api.formatter.label,
                            searchList: { '1': __("即时"), '2': __("延迟") },
                        },
                        {field: 'dir', title: __('Dir'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content,sortable: true},
                        {field: 'history_table_name', title: __('History_table_name'), operate: 'LIKE',sortable: true, table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });
            // 为表格绑定事件
            Table.api.bindevent(table);

            $(document).on("click", ".btn-batch_updates", function () {
                Layer.confirm('确定要批量修改分类？', {icon: 3, title:'提示'},
                    function(index){
                        layer.close(index);
                        var ids = Table.api.selectedids(table);//获取选中列的id
                        if(ids.length==0){
                            layer.alert("最少选择一条记录操作");
                            return false;
                        }
                        Fast.api.open("room/room/batch_updates?ids="+ids.join(','), '批量修改分类',{area:['50%', '60%']});
                    }
                );
            });

        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api_post: function () {

            $("#c-room_id").data("params", function(){
                return {custom: {type_id: $("#c-type_id").val()}};
            });

            //修改堂口的时候触发事件
            $(document).on("change", "#c-type_id", function(){
                //修改堂口后清空分谱支谱当前选择值
                $("#c-room_id_text").selectPageClear();

            });

            Controller.api.bindevent();
        },
        batch_updates: function () {
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