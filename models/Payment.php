<?php

namespace yii\payment\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\web\NotFoundHttpException;

class Payment extends ActiveRecord {

	public static function tableName() {
		return '{{%payment}}';
	}

	public function behaviors() {
		return [
			TimestampBehavior::className(),
		];
	}

	/**
	 * 验证数据
	 * @method validateData
	 * @param {string} $hash 加密hash串
	 * @param {string} $key 密钥
	 * @return {boolean}
	 * @example $this->validateData($hash, $key);
	 */
	public function validateData($hash, $key) {
		return Yii::$app->security->compareString($hash, $this->generateDataHash($key));
	}

	/**
	 * 生成加密hash串
	 * @method generateDataHash
	 * @param {string} $key 密钥
	 * @return {string}
	 * @example $this->generateDataHash($key);
	 */
	public function generateDataHash($key) {
		return hash_hmac('sha256', $this->getDataString(), $key, false);
	}

	/**
	 * 获取数据加密串
	 * @method getDataString
	 * @return {string}
	 */
	private function getDataString() {
		return $this->id . $this->oid . $this->amount . $this->mode;
	}

	/**
	 * 按id查找记录
	 * @method findById
	 * @param {number} $id 记录id
	 * @param {boolean} $toArray 数组格式化
	 * @return {object}
	 * @example static::findById($id, $toArray);
	 */
    public static function findById($id, $toArray = false) {
		$data = static::findOne($id);
		if(!$data) {
			throw new NotFoundHttpException(\Yii::t('common', 'Data query failed'));
		}
		if($toArray) {
			$data = $data->toArray();
		}

		return $data;
	}

}
