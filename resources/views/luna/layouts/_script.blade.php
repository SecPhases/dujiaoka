<script>
    let tipsMsg = {
        least_one    : '{{ __('luna.least_one') }}',
        exceeds      : '{{ __('luna.exceeds') }}',
        exceeds_limit: '{{ __('luna.exceeds_limit') }}',
        mobile_order : '{{ __('luna.mobile_order') }}'
    };
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
    // 获取弹出型div和覆盖层的引用
    var popupDiv = document.getElementById("layui-layer1");
    var overlayDiv = document.getElementById("layui-layer-shade1");

    // 添加点击事件监听器
    overlayDiv.addEventListener("click", function () {
        // 删除弹出型div和覆盖层
        popupDiv.remove();
        overlayDiv.remove();
    });
    });
</script>
<script src="/assets/luna/layui/layui.js"></script>
<script src="/assets/luna/js/jquery-3.4.1.min.js"></script>
<script src="/assets/luna/main.js"></script>
<script src="/assets/luna/layui/lay/modules/layer.js"></script>
