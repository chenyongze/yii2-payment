<?php

namespace yii\payment\controllers;

use Yii;
use yii\base\ErrorException;
use yii\web\Controller;

class TransactionController extends Controller{

	public $defaultAction = 'pay';

	public function actionPay($id, $hash = null){
		$manager = $this->module->manager;
		$mode = $manager->getMode($id);

		if($manager->disabledMode($mode)){
			throw new ErrorException('Payment has been disabled');
		}

		$urlManager = \Yii::$app->urlManager;
		$callbackRoute = DIRECTORY_SEPARATOR . $this->module->id . DIRECTORY_SEPARATOR . $mode . DIRECTORY_SEPARATOR;
		$payUrl = $manager->getPayUrl($id, $urlManager->createAbsoluteUrl([$callbackRoute . 'async'], $manager->protocol), $urlManager->createAbsoluteUrl([$callbackRoute . 'sync'], $manager->protocol), $hash);

		if(empty($payUrl)){
			throw new ErrorException('Payment order abnormal');
		}

		return in_array($mode, ['unionpay', 'psbc', 'bolz']) ? $payUrl : $this->redirect($payUrl);
	}

}
