<?php

namespace yii\payment\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\payment\apis\Wxpay;

class WxpayController extends Controller{

	public $enableCsrfValidation = false;

	private $mode = 'wxpay';

	public function behaviors(){
		return [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'async' => ['post'],
					'package' => ['post'],
				],
			],
		];
	}

	public function actionPackage(){
		return Wxpay::xmlFormatter($this->module->manager->getPackage(\Yii::$app->urlManager->createAbsoluteUrl($this->module->id . DIRECTORY_SEPARATOR . $this->id . DIRECTORY_SEPARATOR . 'async')));
	}

	public function actionAsync(){
		$response = ['return_code' => 'FAIL', 'return_msg' => '参数格式校验错误'];
		$post = Wxpay::getXmlPostData();
		if(isset($post['return_code']) && isset($post['result_code']) && isset($post['out_trade_no']) && isset($post['transaction_id']) && isset($post['transaction_id'])){
			$id = $post['out_trade_no'];
			$tid = $post['transaction_id'];
			$status = $post['return_code'] == 'SUCCESS' && $post['result_code'] == 'SUCCESS';
			$manager = $this->module->manager;
			$verified = $manager->verifySign($this->mode, true);
			$manager->saveNotify($this->mode, $id, $tid, $status, $verified, $post);

			if($verified){
				$response['return_code'] = 'SUCCESS';
				$response['return_msg'] = 'OK';
			}else{
				$response['return_msg'] = '签名验证失败';
			}

			if($status && $manager->complete($id, $tid) && $asyncClass = $this->module->asyncClass){
				$asyncClass::paied($id);
			}
		}

		return Wxpay::xmlFormatter($response);
	}

}
