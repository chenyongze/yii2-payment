<?php

namespace yii\payment\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class PsbcController extends Controller{

	public $enableCsrfValidation = false;

	private $mode = 'psbc';

	public function behaviors(){
		return [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'sync' => ['post'],
				],
			],
		];
	}

	public function actionSync(){
		if(empty($_POST) || !isset($_POST['Plain']) || !isset($_POST['Signature'])){
			return false;
		}

		$plain = json_decode('{"' . str_replace('=', '":"', str_replace('|', '","', trim($_POST['Plain'], '|'))) . '"}');
		$id = $plain->TermSsn;
		$tid = $plain->AcqSsn;
		$status = $this->checkTradeStatus($plain->RespCode) ? 1 : 0;
		$manager = $this->module->manager;
		$verified = $manager->verifySign($this->mode);
		$manager->saveNotify($this->mode, $id, $tid, $status, $verified, $_POST);

		if(!$verified){
			return false;
		}

		if($status && $manager->complete($id, $tid) && $asyncClass = $this->module->asyncClass){
			$asyncClass::paied($id);
		}

		return $this->module->syncRoute ? $this->redirect([$this->module->syncRoute, 'id' => $id]) : $status;
	}

	private function checkTradeStatus($RespCode){
		return $RespCode == '0000000';
	}

}
