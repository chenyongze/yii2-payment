<?php

namespace yii\payment\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class AlipayController extends Controller{

	public $enableCsrfValidation = false;

	private $mode = 'alipay';

	public function behaviors(){
		return [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'async' => ['post'],
					'sync' => ['get'],
				],
			],
		];
	}

	public function actionAsync(){
		if(empty($_POST) || !isset($_POST['out_trade_no']) || !isset($_POST['trade_no']) || !isset($_POST['trade_status'])){
			return false;
		}

		$id = $_POST['out_trade_no'];
		$tid = $_POST['trade_no'];
		$status = $this->checkTradeStatus($_POST['trade_status']) ? 1 : 0;
		$manager = $this->module->manager;
		$verified = $manager->verifySign($this->mode, true);
		$manager->saveNotify($this->mode, $id, $tid, $status, $verified, $_POST);

		if(!$verified){
			return false;
		}

		if($status && $manager->complete($id, $tid) && $asyncClass = $this->module->asyncClass){
			$asyncClass::paied($id);
		}

		return 'success';
	}

	public function actionSync(){
		if(!$this->module->manager->verifySign($this->mode)){
			return false;
		}

		$request = \Yii::$app->request;
		if($this->checkTradeStatus($request->get('trade_status'))){
			return $this->module->syncRoute ? $this->redirect([$this->module->syncRoute, 'id' => $request->get('out_trade_no')]) : '付款成功';
		}

		return true;
	}

	private function checkTradeStatus($trade_status){
		return $trade_status == 'TRADE_SUCCESS' || $trade_status == 'TRADE_FINISHED';
	}

}
