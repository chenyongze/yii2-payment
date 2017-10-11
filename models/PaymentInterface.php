<?php

namespace yii\payment\models;

interface PaymentInterface {

	/**
	 * 完成支付 - 异步通知后调用
	 * @method paied
	 * @since 0.0.1
	 * @param {string} $id 支付单id
	 * @return {none}
	 * @example static::paied($id);
	 */
	public static function paied($id);

}
