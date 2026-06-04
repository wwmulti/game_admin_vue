define(['jquery', 'bootstrap', 'backend', 'table', 'form','editable'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ttpg/rtp_details/index' + location.search,
                    add_url: 'ttpg/rtp_details/add' + location.search,
                    edit_url: 'ttpg/rtp_details/edit',
                    del_url: 'ttpg/rtp_details/del',
                    batch_updates_url: 'ttpg/rtp_details/batch_updates',
                    batch_merchant_url: 'ttpg/rtp_details/batch_merchant',
                    batch_config_url: 'ttpg/rtp_details/batch_config',
                    update_url: 'ttpg/rtp_details/edit_update' + location.search,
                    table: 'ttpg',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: '',
                sortName: '',
                fixedColumns: true,
                fixedRightNumber: 1,
                dblClickToEdit: false,
                showToggle: false,
                showColumns: false,
                showExport: false,
                search: false,
                commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        {
                            field: 'rtp_id',
                            title: __('所属RTP'),
                            searchList: $.getJSON("ttpg/rtp_details/rtp_list"),
                            // addClass: "selectpage",
                            // extend: "data-source='test/spin_statistic/room_name' data-field='name' value=''",
                        },
                        {field: 'room_id', title: __('游戏ID'),sortable: false,operate: false},
                        {field: 'name', title: __('游戏名称'),sortable: true,operate: false},
                        {field: 'bet_amount', title: __('下注金额'),sortable: true,operate: false},
                        // {field: 'group_id', title: __('分组详情'),sortable: true,operate: false},
                        {field: 'reward_id', title: __('分组ID'),sortable: true,operate: false,
                            editable: {
                                type: 'select',
                                source: RoomGroupConfig
                            }
                        },
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
                                    classname: "btn btn-xs btn-success  btn-dialog",
                                    //弹窗位置，//自带参数ids
                                    url: function (row) {
                                        return 'ttpg/rtp_details/edit?room_id=' + row.room_id+"&bet_amount="+row.bet_amount+"&rtp_id="+row.rtp_ids;
                                    },
                                    //参数回传的数据
                                    callback: function (data) {},
                                },
                            ],
                        },
                    ]
                ],
                onEditableSave:function (field, row, oldValue, $el) {
                    var data = {};  //组合更新数据
                    data.room_id =row.room_id;
                    data.bet_amount =row.bet_amount;
                    data.reward_id =row.reward_id;
                    Fast.api.ajax({
                        url: $.fn.bootstrapTable.defaults.extend.update_url,
                        data: data
                    },function () {
                        $(".btn-refresh").trigger("click");
                    });
                },
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
            $(document).on("click", ".btn-batch_updates", function () {
                Layer.confirm('确定要批量配置？', {icon: 3, title:'提示'},
                    function(index){
                        layer.close(index);
                        var data = [];
                        var ids = Table.api.selecteddata(table);//获取选中列的id
                        var room_id = '';
                        var rtp_id = '';
                        for (var i=0;i<ids.length;i++){
                            data.push(ids[i].bet_amount);
                            room_id = ids[i].room_id;
                            rtp_id = ids[i].rtp_ids;
                        }
                        if(data.length==0){
                            layer.alert("最少选择一条记录操作");
                            return false;
                        }
                        Fast.api.open("ttpg/rtp_details/batch_updates?ids="+data.join(',')+'&room_id='+room_id+'&rtp_id='+rtp_id, '批量配置',{area:['50%', '60%']});
                    }
                );
            });
            $(document).on("click", ".btn-batch_merchant", function () {
                Layer.confirm('确定要RTP批量配置？', {icon: 3, title:'提示'},
                    function(index){
                        layer.close(index);
                        var data = [];
                        var ids = Table.api.selecteddata(table);//获取选中列的id
                        var room_id = '';
                        for (var i=0;i<ids.length;i++){
                            data.push(ids[i].id);
                            room_id = ids[i].room_id;
                        }
                        if(data.length==0){
                            layer.alert("最少选择一条记录操作");
                            return false;
                        }
                        Fast.api.open("ttpg/rtp_details/batch_merchant?ids="+data.join(',')+'&room_id='+room_id, 'RTP批量配置',{area:['50%', '60%']});
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
        batch_updates: function () {
            Controller.api.bindevent();
        },
        batch_merchant: function () {
            Controller.api.bindevent();
        },
        batch_config: function () {
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