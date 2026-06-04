define(["jquery", "bootstrap", "backend", "table", "form"], function (
  $,
  undefined,
  Backend,
  Table,
  Form
) {
  var Controller = {
    index: function () {
      // 初始化表格参数配置
      Table.api.init({
        extend: {
          index_url: "room/type/index" + location.search,
          add_url: "room/type/add",
          edit_url: "room/type/edit",
          del_url: "room/type/del",
          multi_url: "room/type/multi",
          import_url: "room/type/import",
          table: "game_type",
        },
      });

      var table = $("#table");

      // 初始化表格
      table.bootstrapTable({
        url: $.fn.bootstrapTable.defaults.extend.index_url,
        pk: "id",
        sortName: "id",
        search:false,
        columns: [
          [
            { checkbox: true },
            { field: "id", title: __("Id") },
            {
              field: "name",
              title: __("name"),
              operate: "LIKE",
            },
            {
              field: "abbreviation",
              title: __("简称"),
              operate: "LIKE",
            },
            {
              field: "type_switch",
              title: __("开关"),
              align: "center",
              table: table,
              formatter: Table.api.formatter.toggle,
              searchList: { 1: __("启用"), 0: __("禁用") },
            },
            // {field: 'image', title: __('image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
            {
              field: "operate",
              title: __("Operate"),
              table: table,
              events: Table.api.events.operate,
              formatter: Table.api.formatter.operate,
            },
          ],
        ],
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
      },
    },
  };
  return Controller;
});
