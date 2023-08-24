<?php

namespace App\Http\Controllers\Pay;

use App\Exceptions\RuleValidationException;
use App\Http\Controllers\PayController;
use Illuminate\Http\Request;


class YmqController extends PayController
{
    private $api_host = "https://open.yunmianqian.com"; //可以更改接口域名

    public function gateway(string $payway, string $orderSN)
    {
        try {
            // 加载网关
            $this->loadGateWay($orderSN, $payway);
            // 构造订单基础信息

            $app_id = $this->payGateway->merchant_id;
            $out_order_sn = $this->order->order_sn;
            $name = $this->order->title;
            $pay_way = strpos($payway, 'wechat') === false ? 'alipay' : 'wechat';
            $price = bcmul($this->order->actual_price, 100, 0);
            $notify_url = url($this->payGateway->pay_handleroute . '/notify_url');
            $key = $this->payGateway->merchant_pem;

            $data = [
                "app_id" => $app_id,
                "out_order_sn" => $out_order_sn,
                "name" => $name,
                "pay_way" => $pay_way,
                "price" => $price,
                "notify_url" => $notify_url,
                "sign" => md5($app_id . $out_order_sn . $name . $pay_way . $price . $notify_url . $key)];

            $ch = curl_init(); //使用curl请求
            curl_setopt($ch, CURLOPT_URL, "{$this->api_host}/api/pay??order_cache=true");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $ymqpay_json = curl_exec($ch);
            curl_close($ch);

            $ymqpay_data = json_decode($ymqpay_json, true);
            if ($ymqpay_data['code'] !== 200) {
                throw new RuleValidationException($ymqpay_data['msg']);
            } else {
                $order_sn = $ymqpay_data['data']['order_sn'];
                $price = $ymqpay_data['data']['price'];
                $qr_price = $ymqpay_data['data']['qr_price'];
                $pay_price = $ymqpay_data['data']['pay_price'];
                $qr = $ymqpay_data['data']['qr'];
                $expire_time = $ymqpay_data['data']['expire_in'];

                $result['payname'] = $this->payGateway->pay_name;
                $result['actual_price'] = $pay_price / 100;
                $result['orderid'] = $this->order->order_sn;
                $result['qr_code'] = $qr;
                return $this->render('static_pages/qrpay', $result, __('dujiaoka.scan_qrcode_to_pay'));
            }
        } catch (RuleValidationException $exception) {
            return $this->err($exception->getMessage());
        }
    }


    public function notifyUrl(Request $request)
    {
        $app_id = rawurldecode($_POST['app_id']);
        $order_sn = rawurldecode($_POST['order_sn']);
        $out_order_sn = rawurldecode($_POST['out_order_sn']);
        $notify_count = rawurldecode($_POST['notify_count']);
        $price = rawurldecode($_POST['price']);
        $qr_price = rawurldecode($_POST['qr_price']);
        $pay_price = rawurldecode($_POST['pay_price']);
        $pay_way = rawurldecode($_POST['pay_way']);
        $created_at = rawurldecode($_POST['created_at']);
        $paid_at = rawurldecode($_POST['paid_at']);
        $server_time = rawurldecode($_POST['server_time']);
        $qr_type = rawurldecode($_POST['qr_type']);
        $sign = rawurldecode($_POST['sign']);

        $order = $this->orderService->detailOrderSN($out_order_sn);
        if (!$order) {
            return 'error';
        }
        $payGateway = $this->payService->detail($order->pay_id);
        if (!$payGateway) {
            return 'error';
        }

        $temp_sign = md5($app_id . $order_sn . $out_order_sn . $notify_count . $pay_way . $price . $qr_type . $qr_price . $pay_price . $created_at . $paid_at . $server_time . $payGateway->merchant_pem);

        if ($temp_sign != $sign) { //不合法的数据
            return '签名错误';
        } else { //合法的数据
            $totalFee = bcdiv($price, 100, 2);
            $this->orderProcessService->completedOrder($out_order_sn, $totalFee, $order_sn);
            return 'success';
        }
    }
}
