<?php

use yii\db\Schema;
use yii\db\Migration;

class m150108_010000_payment extends Migration {

	public function up() {
		$tableOptions = 'engine=innodb character set utf8';
		if($this->db->driverName === 'mysql') {
			$tableOptions .= ' collate utf8_unicode_ci';
		}

		$this->createTable('{{%payment}}', [
			'id' => Schema::TYPE_STRING . '(50) primary key comment "id"',
			'oid' => Schema::TYPE_TEXT . ' not null comment "订单id, 多个以英文逗号隔开"',
			'type' => Schema::TYPE_BOOLEAN . ' not null default 1 comment "支付单类型: 1普通支付单"',
			'title' => Schema::TYPE_STRING . ' not null comment "订单名称"',
			'description' => Schema::TYPE_STRING . ' comment "描述信息"',
			'url' => Schema::TYPE_TEXT . ' comment "商品展示url"',
			'amount' => Schema::TYPE_BIGINT . ' unsigned not null default 0 comment "支付总额(分)"',
			'mode' => Schema::TYPE_STRING . '(50) not null comment "支付方式"',
			'tid' => Schema::TYPE_STRING . '(50) comment "第三方支付端流水号"',
			'expired_at' => Schema::TYPE_INTEGER . ' not null default 0 comment "过期时间"',
			'completed_at' => Schema::TYPE_INTEGER . ' not null default 0 comment "支付完成时间"',
			'created_at' => Schema::TYPE_INTEGER . ' not null comment "创建时间"',
			'updated_at' => Schema::TYPE_INTEGER . ' not null comment "更新时间"',
		], $tableOptions . ' comment="支付单"');
	}

	public function down() {
		$this->dropTable('{{%payment}}');
	}

}
