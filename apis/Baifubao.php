<?php
/*!
 * yii2 extension - 支付系统 - 百付宝sdk
 * version: 0.0.1
 */

namespace yii\payment\apis;

use Yii;

class Baifubao{

	//百付宝网关
	private $api;

	//币种: 1即时到账支付, 2按订单号查询支付结果
	private $service_code = 1;

	//币种: 1人民币
	private $currency = 1;

	//支付方式: 1余额支付(必须登录百度钱包), 2网银支付(在百度钱包页面上选择银行, 可以不登录百度钱包), 3银行网关支付(直接跳到银行的支付页面, 无需登录百度钱包)
	private $pay_type = 2;

	//字符编码: 1GBK
	private $input_charset = 1;

	//接口版本号
	private $version = 2;

	//验证方式: 1MD5, 2SHA-1
	private $sign_method = 1;

	//配置参数
	private $config;

	/**
	 * 构造器
	 * @method __construct
	 * @since 0.0.1
	 * @param {array} $config 参数数组
	 * @return {none}
	 */
	public function __construct($config){
		$this->config = $config;

		$this->api = $this->isMobile() ? 'https://www.baifubao.com/api/0/pay/0/wapdirect?' : 'https://www.baifubao.com/api/0/pay/0/direct?';
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
		$data = $_GET;

		if(empty($data) || !isset($data['sp_no']) || !isset($data['order_no']) || !isset($data['bfb_order_no']) || !isset($data['bfb_order_create_time']) || !isset($data['pay_time']) || !isset($data['pay_type']) || !isset($data['total_amount']) || !isset($data['fee_amount']) || !isset($data['currency']) || !isset($data['pay_result']) || !isset($data['input_charset']) || !isset($data['version']) || !isset($data['sign']) || !isset($data['sign_method']) || $data['sp_no'] != $this->config['sp_no']){
			return false;
		}

		$sign = $data['sign'];
		unset($data['sign']);

		return \Yii::$app->security->compareString($sign, $this->sign($this->getQeuryString($this->arrKsort($data))));
	}

	/**
	 * 获取支付链接
	 * @method getPayUrl
	 * @since 0.0.1
	 * @param {string} $return_url 异步通知地址
	 * @param {string} $page_url 同步通知地址
	 * @param {string} $order_no 商户订单号
	 * @param {string} $goods_name 订单名称
	 * @param {number} $total_amount 付款金额
	 * @param {int} [$expired_at=0] 过期时间
	 * @return {string}
	 * @example $this->getPayUrl($return_url, $page_url, $order_no, $goods_name, $total_amount, $expired_at);
	 */
	public function getPayUrl($return_url, $page_url, $order_no, $goods_name, $total_amount, $expired_at = 0){
		$params = [
			'service_code'	=> $this->service_code,
			'sp_no' => $this->config['sp_no'],
			'order_create_time' => date('YmdHis'),
			'order_no' => $order_no,
			'goods_name' => $this->utf8ToGbk($goods_name),
			'total_amount' => $total_amount,
			'currency' => $this->currency,
			'return_url' => $return_url,
			'page_url' => $page_url,
			'pay_type' => $this->pay_type,
			'input_charset' => $this->input_charset,
			'version' => $this->version,
			'sign_method' => $this->sign_method,
		];

		//交易的超时时间
		if($expired_at > 0){
			$params['expire_time'] = date('YmdHis', $expired_at);
		}

		$params['sign'] = $this->sign($this->getQeuryString($this->arrKsort($params)));

		return $this->api . http_build_query($params);
	}

	/**
	 * 对queryString进行签名并返回相应的string
	 * @method sign
	 * @since 0.0.1
	 * @param {string} $queryString query string
	 * @return {string}
	 */
	private function sign($queryString){
		$_queryString = $queryString . '&key=' . $this->config['key'];

		$sign = '';
		switch($this->sign_method){
			case 1:
				$sign = md5($_queryString);
				break;
			case 2:
				$sign = sha1($_queryString);
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
	 * utf8转换成gbk
	 * @method utf8ToGbk
	 * @since 0.0.1
	 * @param {string} $str 字符串
	 * @return {string}
	 */
	private function utf8ToGbk($str){
		return iconv('utf-8', 'gb2312//ignore', $str);
	}

	/**
	 * gbk转换成utf8
	 * @method gbkToUtf8
	 * @since 0.0.1
	 * @param {string} $str 字符串
	 * @return {string}
	 */
	private function gbkToUtf8($str){
		return iconv('gb2312', 'utf-8', $str);
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
