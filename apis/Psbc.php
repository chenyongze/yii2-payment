<?php
/*
 * yii2 extension - 支付系统 - 中国邮政储蓄银行sdk
 * version: 0.0.1
 */

namespace yii\payment\apis;

use Yii;

class Psbc{

	//支付网关
	private $api;

	//配置参数
	private $config;

	//商户号
	private $MercCode;

	//默认交易类型
	private $transName = 'IPER';

	//商户数字证书文件路径
	private $signCertPath;

	//商户数字证书密码
	private $signCertPwd;

	//银行支付平台证书路径
	private $verifyCertPath;

	//form表单前缀
	private $name_pre = 'psbc_form_';

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
		$this->MercCode = $this->config['MercCode'];
		$this->signCertPath = $this->config['signCertPath'];
		$this->signCertPwd = $this->config['signCertPwd'];
		$this->dev = isset($this->config['dev']) && $this->config['dev'];

		if($this->dev){
			$this->api = 'http://103.22.255.201:8443/psbcpay/main';
			$this->verifyCertPath = __DIR__ . '/psbc_verify_dev.cer';
		}else{
			$this->api = 'https://pbank.psbc.com/psbcpay/main';
			$this->verifyCertPath = __DIR__ . '/psbc_verify_prod.cer';
		}

		if($this->isMobile()){
			$this->transName = 'WPER';
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
	 * @return {boolean}
	 * @example $this->verifySign();
	 */
	public function verifySign(){
		if(empty($_POST) || !isset($_POST['Plain']) || !isset($_POST['Signature'])){
			return false;
		}

		$cer = file_get_contents(\Yii::getAlias($this->verifyCertPath));
		$_cer = openssl_x509_read($cer);
		$pkey = openssl_get_publickey($_cer);
		$result = openssl_verify($_POST['Plain'], hex2bin($_POST['Signature']), $pkey, OPENSSL_ALGO_MD5);
		openssl_free_key($pkey);

		return $result;
	}

	/**
	 * 获取支付参数
	 * @method getPayUrl
	 * @since 0.0.1
	 * @param {string} $MercUrl 通知地址
	 * @param {string} $TermSsn 商户订单号
	 * @param {number} $TranAmt 交易金额
	 * @return {string}
	 * @example $this->getPayUrl($MercUrl, $TermSsn, $TranAmt);
	 */
	public function getPayUrl($MercUrl, $TermSsn, $TranAmt){
		$data = http_build_query([
			'TranAbbr' => $this->transName,
			'MercDtTm' => date('YmdHis'),
			'TermSsn' => $TermSsn,
			'MercCode' => $this->MercCode,
			'TranAmt' => $TranAmt,
			'MercUrl' => $MercUrl,
			'TermCode' => '',
		], null, '|');

		$params = [
			'transName' => $this->transName,
			'Plain' => $data,
			'Signature' => $this->sign($data),
		];

		return $this->createPostForm($params);
	}

	/**
	 * 签名
	 * @method sign
	 * @since 0.0.1
	 * @param {array} $data 签名数据
	 * @return {string}
	 */
	private function sign($data){
		$pkcs12 = file_get_contents(\Yii::getAlias($this->signCertPath));
		openssl_pkcs12_read($pkcs12, $certs, $this->signCertPwd);
		$private_key = openssl_get_privatekey($certs['pkey']);
		$sign_falg = openssl_sign($data, $signature, $private_key, OPENSSL_ALGO_MD5);
		openssl_free_key($private_key);

		return bin2hex($signature);
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
		$form = ['<form action="' . $this->api . '" method="post" name="' . $id . '">'];
		foreach($params as $name => $value){
			$form[] = '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
		}
		$form[] = '</form><script type="text/javascript">document.' . $id. '.submit();</script>';

		return implode('', $form);
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
