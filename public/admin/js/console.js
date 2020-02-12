"use strict";
layui.use(["okUtils", "table", "okCountUp", "okMock"], function () {
    var countUp = layui.okCountUp;
    var table = layui.table;
    var okUtils = layui.okUtils;
    var okMock = layui.okMock;
    var $ = layui.jquery;

    var userSourceOption = {
        "title": {"text": ""},
        "tooltip": {"trigger": "axis", "axisPointer": {"type": "cross", "label": {"backgroundColor": "#6a7985"}}},
        "legend": {"data": ["积分", "余额"]},
        "toolbox": {"feature": {"saveAsImage": {}}},
        "grid": {"left": "3%", "right": "4%", "bottom": "3%", "containLabel": true},
        "xAxis": [{"type": "category", "boundaryGap": false, "data": ["周一", "周二", "周三", "周四", "周五", "周六", "周日"]}],
        "yAxis": [{"type": "value"}],
        "series": [
            {"name": "积分", "type": "line", "stack": "总量", "areaStyle": {}, "data": [0, 0, 0, 0, 0, 0, 0]},
            {"name": "余额", "type": "line", "stack": "总量", "label": {"normal": {"show": true, "position": "top"}}, "areaStyle": {"normal": {}}, "data": [0, 0, 0, 0, 0, 0, 0]}
        ]
    };

    /**
     * 用户访问
     */
    function userSource() {
        var userSourceMap = echarts.init($("#userSourceMap")[0], "theme");
        userSourceMap.setOption(userSourceOption);
        okUtils.echartsResize([userSourceMap]);
    }

    userSource();
});


