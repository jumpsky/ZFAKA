<?php
/**
 * File: flyfoxpay.php
 * Functionality: 翔狐科技
 * Author: 翔狐科技
 * Date: 2019-5-14
 */
namespace Pay\flyfoxpay;
use \Pay\notify;
function getTinyUrl($url) { return file_get_contents("https://tinyurl.com/api-create.php?url=".$url); }
class Pays
{
    private $pid;
    private $key;
    public function __construct($pid, $key)
    {
        $this->pid = $pid;
        $this->key = $key;
    }
    /**
     * @Note  支付发起
     * @param $type   支付方式
     * @param $out_trade_no     订单号
     * @param $notify_url     异步通知地址
     * @param $return_url     回调通知地址
     * @param $name     商品名称
     * @param $money     金额
     * @param $sitename     站点名称
     * @return string
     */
    public function submit($type, $out_trade_no, $notify_url, $return_url, $name, $money, $sitename)
    {
        $data = [
            'pid' => $this->pid,
            'type' => $type,
            'out_trade_no' => $out_trade_no,
            'notify_url' => $notify_url,
            'return_url' => $return_url,
            'name' => $name,
            'money' => $money,
            'sitename' => $sitename
        ];
        $string = http_build_query($data);
        $sign = $this->getsign($data);
        return 'https://api.jxspay.cn/submit/' . $string . '&sign=' . $sign . '&sign_type=MD5';
    }
    /**
     * @Note   验证签名
     * @param $data  待验证参数
     * @return bool
     */
    public function verify($data)
    {
        if (!isset($data['sign']) || !$data['sign']) {
            return false;
        }
        $sign = $data['sign'];
        unset($data['sign']);
        unset($data['sign_type']);
        $sign2 = $this->getSign($data, $this->key);
        if ($sign != $sign2) {
            return false;
        }
        return true;
    }
    /**
     * @Note  生成签名
     * @param $data   参与签名的参数
     * @return string
     */
    private function getSign($data)
    {
        $data = array_filter($data);
        ksort($data);
        $str1 = '';
        foreach ($data as $k => $v) {
          if($k=="paymethod"){}else{
            $str1 .= '&' . $k . "=" . $v;
          }
        }
        $str = $str1 . $this->key;
        $str = trim($str, '&');
        $sign = md5($str);
        return $sign;
    }
}

class flyfoxpay
{
	private $paymethod ="flyfoxpay";
	//处理请求
	public function pay($payconfig,$params)
	{
		try{
	$return='https://'.$_SERVER['HTTP_HOST'].'/product/notify/?paymethod=flyfoxpay';
          $return1='https://'.$_SERVER['HTTP_HOST'].'/query/auto/'.$params['orderid'].'.html';
		$pays = new Pays($payconfig['app_id'], $payconfig['app_secret']);
//支付方式
$type = 'all';
//订单号
$out_trade_no = $params['orderid'];
//异步通知地址
$notify_url = $return;
//回调通知地址
$return_url = $return1;
//商品名称
$name = '虛擬商品';
//支付金额（保留小数点后两位）
$money = $params['money'];
//站点名称
$sitename = $_SERVER['HTTP_HOST'];
//发起支付
$url = $pays->submit($type, $out_trade_no, $notify_url, $return_url, $name, $money, $sitename);
$urls1=getTinyUrl($url);
				$qr="https://pay.ncepay.com/tool/qr.php?level=L&size=4&data=".$urls1;
				$result_params = array('type'=>0,'subjump'=>0,'paymethod'=>"flyfoxpay",'qr'=>$qr,'payname'=>'二维码扫描器','overtime'=>'0','money'=>$params['money']);
				return array('code'=>1,'msg'=>'success','data'=>$result_params);
			
		} catch (\Exception $e) {
			return array('code'=>1000,'msg'=>$e->getMessage(),'data'=>'');
		}}
	
	
	public function notify(array $payconfig)
	{
		try {
          $pays = new Pays($payconfig['app_id'], $payconfig['app_secret']);

//接收异步通知数据
$data = $_GET;

//商户订单号
$out_trade_no = $data['out_trade_no'];

//验证签名
if ($pays->verify($data)) {
    //验证支付状态
    if ($data['trade_status'] == 'TRADE_SUCCESS') {
        echo 'success';
      $config = array('paymethod'=>"flyfoxpay",'tradeid'=>$data['trade_no'],'paymoney'=>$data['money'],'orderid'=>$data['out_trade_no']);
					$notify = new \Pay\notify();
					$data1 = $notify->run($config);
        //这里就可以放心的处理您的业务流程了
        //您可以通过上面的商户订单号进行业务流程处理
    }
} else {
 
    echo 'fail';
}
	  
} catch (\Exception $e) {
			return 'error|Exception:'.$e->getMessage();
		}
	}
	
}
