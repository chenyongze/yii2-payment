<?php
/*
 * yii2 extension - 支付系统 - 微信支付sdk
 */

namespace yii\payment\apis;

use Yii;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

class Wxpay{

	//扫码接口
	private $bizpayurl = 'weixin://wxpay/bizpayurl?';

	//统一下单接口
	private $unifiedorder = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

	//统一下单接口
	private $snsapi = 'https://open.weixin.qq.com/connect/oauth2/authorize?';

	//获取access token地址
	private $tokenapi = 'https://api.weixin.qq.com/sns/oauth2/access_token?';

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
	 * 获取openid
	 * @method getOpenId
	 * @since 0.0.1
	 * @param {string} $code 票据
	 * @return {string}
	 * @example $this->getOpenId($code);
	 */
	public function getOpenId($code){
		$data = Json::decode($this->curl($this->tokenapi . $this->getQeuryString([
			'appid' => $this->config['appid'],
			'secret' => $this->config['secret'],
			'code' => $code,
			'grant_type' => 'authorization_code',
		])));

		return isset($data['openid']) ? $data['openid'] : false;
	}

	/**
	 * 获取网页授权地址
	 * @method getSnsapiUrl
	 * @since 0.0.1
	 * @param {string} $redirect_uri 回调地址
	 * @param {string} [$scope='snsapi_base'] 授权作用域
	 * @param {string} [$state=null] 返回参数
	 * @return {array}
	 * @example $this->getSnsapiUrl($redirect_uri, $scope, $state);
	 */
	public function getSnsapiUrl($redirect_uri, $scope = 'snsapi_base', $state = null){
		return $this->snsapi . http_build_query([
			'appid' => $this->config['appid'],
			'redirect_uri' => $redirect_uri,
			'response_type' => 'code',
			'scope' => $scope,
			'state' => $state,
		]) . '#wechat_redirect';
	}

	/**
	 * 创建支付交易
	 * @method createUnifiedOrder
	 * @since 0.0.1
	 * @param {array} $post 数据
	 * @param {string} $notify_url 异步通知地址
	 * @param {string} [$trade_type='NATIVE'] 交易类型	
	 * @param {string} [$device_info='WEB'] 设备号
	 * @return {array}
	 * @example $this->createUnifiedOrder($data, $notify_url, $trade_type, $device_info);
	 */
	public function createUnifiedOrder($data, $notify_url, $trade_type = 'NATIVE', $device_info = 'WEB'){
		$params = [
			'appid' => $this->config['appid'],
			'mch_id' => $this->config['mch_id'],
			'nonce_str' => $this->createNonceStr(),
			'device_info' => $device_info,
			'body' => $data['title'],
			'out_trade_no' => $data['id'],
			'total_fee' => $data['amount'],
			'spbill_create_ip' => \Yii::$app->request->userIP,
			'notify_url' => $notify_url,
			'trade_type' => $trade_type,
		];

		if(isset($data['description']) && !empty($data['description'])){
			$params['detail'] = $data['description'];
		}

		if(isset($data['expired_at']) && !empty($data['expired_at'])){
			$params['time_expire'] = date('YmdHis', $data['expired_at']);
		}

		switch($params['trade_type']){
			case 'NATIVE':
				$params['product_id'] = $data['product_id'];
				break;
			case 'JSAPI':
				$params['openid'] = $data['openid'];
				break;
		}

		$params['sign'] = $this->sign($params);
		
		return static::getXmlPostData($this->curl($this->unifiedorder, static::xmlFormatter($params)));
	}

	/**
	 * 创建二维码地址链接
	 * @method createBizpayurl
	 * @since 0.0.1
	 * @param {string} $product_id 商品id
	 * @return {string}
	 * @example $this->createBizpayurl($product_id);
	 */
	public function createBizpayurl($product_id){
		$params = [
			'appid' => $this->config['appid'],
			'mch_id' => $this->config['mch_id'],
			'time_stamp' => time(),
			'nonce_str' => $this->createNonceStr(),
			'product_id' => $product_id,
		];

		$params['sign'] = $this->sign($params);

		return $this->bizpayurl . $this->getQeuryString($params);
	}

	/**
	 * 将数组格式化成xml
	 * @method xmlFormatter
	 * @since 0.0.1
	 * @param {array} $params 参数
	 * @return {string}
	 * @example static::xmlFormatter($params);
	 */
	public static function xmlFormatter($params){
		$xml = ['<xml>'];
		foreach($params as $key => $value){
			$xml[] = "<$key><![CDATA[$value]]></$key>";
		}
		$xml[] = '</xml>';

		return implode('', $xml);
	}

	/**
	 * 获取post头部xml数据
	 * @method getXmlPostData
	 * @since 0.0.1
	 * @param {object} $postStr post数据
	 * @return {array}
	 * @example static::getXmlPostData();
	 */
	public static function getXmlPostData($postStr = null){
		if(empty($postStr)){
			$postStr = @$GLOBALS['HTTP_RAW_POST_DATA'];
		}

		return empty($postStr) ? null : @json_decode(@json_encode((array) simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
	}

	/**
	 * 验证签名
	 * @method verifySign
	 * @since 0.0.1
	 * @param {array} $data 待验证数据
	 * @return {boolean}
	 * @example $this->verifySign($data);
	 */
	public function verifySign($data = null){
		if(empty($data)){
			$data = static::getXmlPostData();
		}
		$sign = $data['sign'];
		$data['sign'] = null;
		
		return \Yii::$app->security->compareString($sign, $this->sign($data));
	}

	/**
	 * 生成签名
	 * @method sign
	 * @since 0.0.1
	 * @param {array} $params 参与签名的参数
	 * @return {string}
	 */
	public function sign($params){
		return strtoupper(md5($this->getQeuryString($this->arrKsort($params)) . '&key=' . $this->config['key']));
	}

	/**
	 * 获取querystring
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
	 * 生成随机字符串(不长于32位)
	 * @method createNonceStr
	 * @since 0.0.1
	 * @return {string}
	 */
	private function createNonceStr(){
		return md5(mt_rand());
	}

	/**
	 * curl远程获取数据方法
	 * @method curl
	 * @since 1.0.0
	 * @param {string} $url 请求地址
	 * @param {array|string} [$data=null] post数据
	 * @param {string} [$useragent=null] 模拟浏览器用户代理信息
	 * @return {string} 返回获取的数据
	 */
	private function curl($url, $data = null, $useragent = null){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		if(isset($data)){
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}
		if(isset($useragent)){
			curl_setopt($curl, CURLOPT_USERAGENT, $useragent);
		}
		$result = curl_exec($curl);
		curl_close($curl);

		return $result;
	}

}
