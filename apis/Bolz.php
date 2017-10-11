<?php
/*!
 * yii2 extension - 支付系统 - 柳州银行sdk
 * version: 0.0.1
 */

namespace yii\payment\apis;

use Yii;

class Bolz{

	//柳州银行网关
	private $api = 'https://epay.bolz.cn/epaygate/';

	//即时到账交易接口参数
	private $params = [
		'service' => 'pay_service',	//接口名称
		'trade_mode' => '0002',	//交易类型, 0001担保支付, 0002即时到账
		'fee_type' => 1,	//币种
		'trans_channel' => 'pc',	//支付渠道, pc为PC端, mb为移动端
	];

	//验证方式
	private $sign_type = 'MD5';

	//配置参数
	private $config;

	//form表单前缀
	private $name_pre = 'bolz_form_';

	//开发模式
	private $dev;

	/**
	 * 构造器
	 * @method __construct
	 * @since 0.0.1
	 * @param {array} $config 参数数组
	 * @return {none}
	 */
	public function __construct($config){
		$this->config = $config;
		$this->dev = isset($this->config['dev']) && $this->config['dev'];

		if($this->dev){
			$this->api = 'http://testepay.bolz.cn:4080/epaygate/';
		}

		if($this->isMobile()){
			$this->params['trans_channel'] = 'mb';
		}
	}

	/**
	 * 获取类对象
	 * @method sdk
	 * @since 0.0.1
	 * @param {array} $config 参数数组
	 * @return {none}
	 * @example static::sdk($config);
	 */
	public static function sdk($config){
		return new static($config);
	}

	/**
	 * 验证签名
	 * @method verifySign
	 * @since 0.0.1
	 * @param {boolean} [$async=false] 是否为异步通知
	 * @return {boolean}
	 * @example $this->verifySign($async);
	 */
	public function verifySign($async = false){
		$data = $async ? $_POST : $_GET;

		if(empty($data) || !isset($data['sign']) || !isset($data['sign_type']) ||  !isset($data['notify_id'])){
			return false;
		}

		$sign = $data['sign'];

		unset($data['sign']);
		unset($data['sign_type']);

		return \Yii::$app->security->compareString($sign, $this->sign($data)) && $this->verifyNotify($data['notify_id']);
	}

	/**
	 * 获取支付链接
	 * @method getPayUrl
	 * @since 0.0.1
	 * @param {string} $notify_url 异步通知地址
	 * @param {string} $return_url 同步通知地址
	 * @param {string} $out_trade_no 商户订单号
	 * @param {string} $subject 订单名称
	 * @param {number} $total_fee 付款金额
	 * @param {string} [$body=null] 订单描述
	 * @param {string} [$show_url=null] 商品展示地址
	 * @param {int} [$expired_at=0] 过期时间
	 * @return {string}
	 * @example $this->getPayUrl($notify_url, $return_url, $out_trade_no, $subject, $total_fee, $body, $show_url, $expired_at);
	 */
	public function getPayUrl($notify_url, $return_url, $out_trade_no, $subject, $total_fee, $body = null, $show_url = null, $expired_at = 0){
		$params = array_merge([
			'partner' => $this->config['partner'],
			'notify_url' => $notify_url,
			'return_url' => $return_url,
			'out_trade_no' => $out_trade_no,
			'subject' => $subject,
			'total_fee' => $total_fee,
			'body' => $body,
			'show_url' => $show_url ? : \Yii::$app->request->hostInfo,
			'spbill_create_ip' => \Yii::$app->request->userIP,
		], $this->params);

		if($expired_at > 0){
			$params['time_expire'] = date('Ymd H:i:s', $expired_at);
		}

		$params['sign'] = $this->sign($params);
		$params['sign_type'] = $this->sign_type;

		return $this->createPostForm($params);
	}

	/**
	 * 消息验证
	 * @method verifyNotify
	 * @since 0.0.1
	 * @param {string} $notify_id 是否为异步通知
	 * @return {boolean}
	 */
	private function verifyNotify($notify_id){
		$result = $this->getHttpResponseGET($this->api . 'notifyIdQuery.htm?partner=' . $this->config['partner'] . '&notify_id=' . $notify_id);
		
		return preg_match("/true$/i", $result);
	}

	/**
	 * 远程获取数据，GET模式
	 * @method getHttpResponseGET
	 * @since 0.0.1
	 * @param {string} $url 指定URL完整路径地址
	 * @return {string}
	 */
	private function getHttpResponseGET($url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$responseText = curl_exec($curl);
		curl_close($curl);

		return $responseText;
	}

	/**
	 * 创建待提交post表单
	 * @method createPostForm
	 * @since 0.0.1
	 * @param {array} $params 参数
	 * @return {string}
	 */
	private function createPostForm($params){
		$id = $this->name_pre . uniqId();
		$form = ['<form action="' . $this->api . 'pay.htm" method="post" name="' . $id . '">'];
		foreach($params as $name => $value){
			$form[] = '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
		}
		$form[] = '</form><script type="text/javascript">document.' . $id. '.submit();</script>';

		return implode('', $form);
	}

	/**
	 * 对参数进行签名
	 * @method sign
	 * @since 0.0.1
	 * @param {array} $params 参数数组
	 * @return {string}
	 */
	private function sign($params){
		$queryString = $this->getQeuryString($this->arrKsort($params)) . $this->config['key'];

		$sign = '';
		switch($this->sign_type){
			case 'MD5':
				$sign = md5($queryString);
				break;
		}

		return $sign;
	}

	/**
	 * 获取queryString
	 * @method getQeuryString
	 * @since 0.0.1
	 * @param {array} $arr 需转换数组
	 * @return {string}
	 */
	private function getQeuryString($arr){
		return urldecode(http_build_query($arr));
	}

	/**
	 * 对签名参数进行数组排序
	 * @method arrKsort
	 * @since 0.0.1
	 * @param {array} $arr 需排序数组
	 * @return {array}
	 */
	private function arrKsort($arr){
		ksort($arr);
		reset($arr);

		return $arr;
	}

	/**
	 * 移动端检测
	 * @method isMobile
	 * @since 0.0.1
	 * @return {boolean}
	 */
	private function isMobile(){
		return isset($_SERVER['HTTP_X_WAP_PROFILE']) || (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], 'wap')) || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(nokia|sony|ericsson|mot|samsung|htc|sgh|lg|sharp|sie-|philips|panasonic|alcatel|lenovo|iphone|ipod|blackberry|meizu|android|netfront|symbian|ucweb|windowsce|palm|operamini|operamobi|openwave|nexusone|cldc|midp|wap|mobile)/i', strtolower($_SERVER['HTTP_USER_AGENT'])));
	}

}
