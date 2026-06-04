define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ttpg/ttpg/index' + location.search,
                    table: 'ttpg',
                }
            });
            var merchant_status = true;
            var merchant_id = '';
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
                        {
                            field: 'merchant_id',
                            title: __('所属商户'),
                            searchList: $.getJSON("ttpg/ttpg/merchant_name"),
                            visible:false,
                        },
                        {field: 'id', title: __('Id'),sortable: true,operate:false},
                        {field: 'name', title: __('Name'), operate: false,sortable: true},
                        {
                            field: 'reward_id', title: __('分组ID'), operate: false,
                            formatter: Table.api.formatter.normal,
                            cellStyle : function(value, row, index, field) {
                                return {
                                    css: {
                                        'width': '100%',
                                        'min-height': '75px',
                                        'display': 'block',
                                        'white-space': 'normal',
                                        'word-wrap': 'break-word',
                                        'word-break': 'break-all',
                                        'overflow': 'hidden',
                                        'overflow-y': 'auto',
                                    }
                                };
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
                                    name: "details",
                                    text: __("详情"),
                                    title: function (row) {
                                        return row.name+' --  '+__("返奖详情");
                                    },
                                    classname: "btn btn-xs btn-primary btn-dialog",
                                    //弹窗位置，//自带参数ids
                                    url: function (row) {
                                        return 'ttpg/details/index?room_id=' + row.id+'&merchant_id='+row.merchant_id;
                                    },
                                    //弹窗大小
                                    extend: 'data-area=\'["80%","95%"]\'',
                                    disable: function(row){
                                        if(row.merchant_id === ""){
                                            return true;
                                        }
                                        return false;
                                    },
                                    //参数回传的数据
                                    callback: function (data) {
                                    },
                                },
                            ],
                        },
                    ]
                ]
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