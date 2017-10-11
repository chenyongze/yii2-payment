<?php

namespace yii\payment\models;

use Yii;
use yii\db\ActiveRecord;

class PaymentNotify extends ActiveRecord {

	public static function tableName() {
		return '{{%payment_notify}}';
	}

}
