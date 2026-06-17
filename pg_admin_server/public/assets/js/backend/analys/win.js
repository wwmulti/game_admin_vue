define(["jquery", "bootstrap", "backend", "table", "form"], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        pollTimer: null,
        pollCount: 0,
        index: function () {
            Controller.winanalys();
        },
        winanalys: function () {
            // 加载商户下拉数据
            $.getJSON("analys/win/merchant_name", function(data) {
                var $merchant = $("#c-merchant");
                $.each(data, function(id, name) {
                    $merchant.append('<option value="'+id+'">'+name+'</option>');
                });
                $merchant.selectpicker("refresh");
            });

            // 加载游戏下拉数据
            $.getJSON("analys/win/rooms", function(data) {
                var $game = $("#c-game");
                $.each(data, function(id, name) {
                    $game.append('<option value="'+id+'">'+name+'</option>');
                });
                $game.selectpicker("refresh");
            });

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "analys/win/index" + location.search,
                    table: "game_analys_tasks",
                },
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: "id",
                sortName: "id",
                dblClickToEdit: false,
                showToggle: false,
                showColumns: false,
                search: false,
                commonSearch: false,
                columns: [
                    [
                        { field: 'task_time', title: __('时间'), formatter: Table.api.formatter.normal },
                        { field: 'merchant_name', title: __('商户'), formatter: Table.api.formatter.normal },
                        { field: 'game_name', title: __('游戏'), formatter: Table.api.formatter.normal },
                        { 
                            field: 'status', 
                            title: __('状态'), 
                            operate: false,
                            formatter: function(value) {
                                var statusMap = { 0: '未执行', 1: '执行中 <i class="fa fa-spinner fa-spin"></i>', 2: '<span class="text-success">执行成功</span>', 3: '<span class="text-danger">执行失败</span>'};
                                return statusMap[value] || value;
                            }
                        },
                        { 
                            field: 'operate', 
                            title: __('操作'), 
                            table: table,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: '详情',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-file-text-o',
                                    extend: 'data-area=\'["80%","80%"]\'',
                                    visible: function(row) {
                                        return row.status == 2;
                                    },
                                    url: function(row) {
                                        return 'analys/win/winAnalysDetail?id=' + row.id;
                                    },
                                },
                            ],
                            formatter: Table.api.formatter.operate,
                        },
                        {
                            field: 'c_time', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: function (value) {
                                return value;
                            }
                        },
                        { field: 'remark', title: __('原因'), operate: false, formatter: function(value) {
                            if (!value) return '-';
                            var show = value.length > 20 ? value.substring(0, 20) + '...' : value;
                            return '<span title="' + value + '">' + show + '</span>';
                        }},
                    ],
                ],
            });
            // 为表格绑定事件
            Table.api.bindevent(table);

            // 初始化表单元素(时间控件等)
            Form.api.bindevent($("#stat-form"));

            // 表单提交 - 使用button点击事件避免重复提交
            $("#stat-form").find('button[type="submit"]').on("click", function(e) {
                e.preventDefault();
                var time = $("#c-time").val();
                var merchant = $("#c-merchant").val();
                var game = $("#c-game").val();
                var params = "stat=1";
                if (time) params += "&time=" + encodeURIComponent(time);
                if (merchant) params += "&merchant=" + encodeURIComponent(merchant);
                if (game) params += "&game=" + encodeURIComponent(game);
                
                layer.confirm('确认执行统计？', function(index) {
                    layer.close(index);
                    var query = {stat: 1};
                    if (time) query.time = time;
                    if (merchant) query.merchant = merchant;
                    if (game) query.game = game;

                    // 先手动提交，检测后端是否报错
                    $.ajax({
                        url: $.fn.bootstrapTable.defaults.extend.index_url,
                        data: query,
                        dataType: 'json',
                        success: function (res) {
                            if (res.code === 0) {
                                // 后端报错，显示错误并返回
                                layer.msg(res.msg, {icon: 2});
                                return;
                            }
                            // 成功，刷新表格并开始轮询
                            layer.msg('统计执行中', {icon: 1});
                            table.bootstrapTable("refresh");
                            // 重置轮询
                            if (Controller.pollTimer) {
                                clearInterval(Controller.pollTimer);
                            }
                            Controller.pollCount = 0;
                            Controller.pollTimer = setInterval(function() {
                                Controller.pollCount++;
                                if (Controller.pollCount >= 10) { // 30秒
                                    clearInterval(Controller.pollTimer);
                                    Controller.pollTimer = null;
                                    layer.msg('统计执行时间过长，请稍候点击刷新按钮刷新数据', {icon: 0});
                                    return;
                                }
                                $.getJSON("analys/win/checkStatus?type=1", function(res) {
                                    if (res.count == 0) {
                                        clearInterval(Controller.pollTimer);
                                        Controller.pollTimer = null;
                                        table.bootstrapTable("refresh");
                                        layer.msg('统计执行完成', {icon: 1});
                                        return;
                                    }
                                });
                            }, 3000);
                        },
                        error: function () {
                            layer.msg('请求失败', {icon: 2});
                        }
                    });
                });
            });
            
            // 阻止表单默认提交
            $("#stat-form").on("submit", function(e) {
                e.preventDefault();
            });
        },
        winAnalysDetail: function () {
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
