<?php
namespace yii\payment\apis;

use Yii;

class Alipay{

	//支付宝网关
	private $api = 'https://mapi.alipay.com/gateway.do?';

	//即时到账交易接口参数
	private $params = [
		'service' => 'create_direct_pay_by_user',	//接口名称
		'payment_type' => 1,	//支付类型
		'anti_phishing_key' => null,	//防钓鱼时间戳
		'exter_invoke_ip' => null,	//客户端的IP地址
		'_input_charset' => 'utf-8',	//字符编码格式
	];

	//验证方式
	private $sign_type = 'MD5';

	//ssl证书
	private $cacert = 'alipay_cacert.pem';

	//配置参数
	private $config;

	//是否移动端
	private $isMobile = false;

	/**
	 * 构造器
	 * @method __construct
	 * @since 0.0.1
	 * @param {array} $config 参数数组
	 * @return {none}
	 */
	public function __construct($config){
		$this->config = $config;

		if($this->isMobile()){
			$this->params['service'] = 'alipay.wap.create.direct.pay.by.user';
			$this->isMobile = true;
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

		return \Yii::$app->security->compareString($sign, $this->sign($this->getQeuryString($this->arrKsort($data)))) && $this->verifyNotify($data['notify_id']);
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
		return $this->buildRequest(array_merge([
			'seller_id' => $this->config['partner'],
			'partner' => $this->config['partner'],
			'notify_url' => $notify_url,
			'return_url' => $return_url,
			'out_trade_no' => $out_trade_no,
			'subject' => $subject,
			'total_fee' => $total_fee,
			'body' => $body,
			'show_url' => $show_url,
			'it_b_pay' => $expired_at > 0 ? ($this->isMobile ? date('Y-m-d H:i:s', $expired_at) : max(1, floor(($expired_at - time()) / 60)) . 'm') : null,
		], $this->params));
	}

	/**
	 * 消息验证
	 * @method verifyNotify
	 * @since 0.0.1
	 * @param {string} $notify_id 是否为异步通知
	 * @return {boolean}
	 */
	private function verifyNotify($notify_id){
		$result = $this->getHttpResponseGET($this->api . 'service=notify_verify&partner=' . $this->config['partner'] . '&notify_id=' . $notify_id, __DIR__ . DIRECTORY_SEPARATOR . $this->cacert);
		
		return preg_match("/true$/i", $result);
	}

	/**
	 * 远程获取数据，GET模式
	 * @method getHttpResponseGET
	 * @since 0.0.1
	 * @param {string} $url 指定URL完整路径地址
	 * @param {string} $cacert_url 指定ssl证书绝对路径
	 * @return {string}
	 */
	private function getHttpResponseGET($url, $cacert_url) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_CAINFO, $cacert_url);
		$responseText = curl_exec($curl);
		curl_close($curl);

		return $responseText;
	}

	/**
	 * 创建支付链接
	 * @method buildRequest
	 * @since 0.0.1
	 * @param {array} $params 参数数组
	 * @return {string}
	 */
	private function buildRequest($params){
		$queryString = $this->getQeuryString($this->arrKsort($params));

		return $this->api . $queryString . '&sign=' . $this->sign($queryString) . '&sign_type=' . $this->sign_type;
	}

	/**
	 * 对queryString进行签名并返回相应的string
	 * @method sign
	 * @since 0.0.1
	 * @param {string} $queryString query string
	 * @return {string}
	 */
	private function sign($queryString){
		$_queryString = $queryString . $this->config['key'];

		$sign = '';
		switch($this->sign_type){
			case 'MD5':
				$sign = md5($_queryString);
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
