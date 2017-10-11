<?php

namespace yii\payment\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class UnionpayController extends Controller{

	public $enableCsrfValidation = false;

	private $mode = 'unionpay';

	public function behaviors(){
		return [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'async' => ['post'],
					'sync' => ['post'],
				],
			],
		];
	}

	public function actionAsync(){
		if(empty($_POST) || !isset($_POST['orderId']) || !isset($_POST['queryId']) || !isset($_POST['respCode']) || !isset($_POST['respMsg'])){
			return false;
		}

		$id = $_POST['orderId'];
		$tid = $_POST['queryId'];
		$status = $this->checkTradeStatus($_POST['respCode'], $_POST['respMsg']) ? 1 : 0;
		$manager = $this->module->manager;
		$verified = $manager->verifySign($this->mode, true);
		$manager->saveNotify($this->mode, $id, $tid, $status, $verified, $_POST);

		if(!$verified){
			return false;
		}

		if($status && $manager->complete($id, $tid) && $asyncClass = $this->module->asyncClass){
			$asyncClass::paied($id);
		}

		return '验签成功';
	}

	public function actionSync(){
		if(!$this->module->manager->verifySign($this->mode)){
			return false;
		}

		$request = \Yii::$app->request;
		if($this->checkTradeStatus($request->post('respCode'), $request->post('respMsg'))){
			return $this->module->syncRoute ? $this->redirect([$this->module->syncRoute, 'id' => $request->post('orderId')]) : '付款成功';
		}

		return true;
	}

	private function checkTradeStatus($respCode, $respMsg){
		return $respCode == '00' && ($respMsg == 'Success!' || $respMsg == 'success');
	}

}
