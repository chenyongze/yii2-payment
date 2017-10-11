<?php

namespace yii\payment;

use Yii;
use yii\payment\models\Payment;

class Module extends \yii\base\Module {

	public $defaultRoute = 'transaction';

	public $defaultComponent = 'payment';

	public $manager;

	//异步通知内部调用类
	public $asyncClass;

	//同步通知内部调用路由
	public $syncRoute;

	public function init() {
		parent::init();

		$this->manager = \Yii::createObject(\Yii::$app->components[$this->defaultComponent]);
	}

}
