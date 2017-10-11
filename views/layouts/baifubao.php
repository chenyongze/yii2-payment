<?php
use yii\helpers\Html;
?>
<?php $this->beginPage(); ?>
<!doctype html>

<!-- begin html -->
<html lang="<?= \Yii::$app->language ?>">

<!-- begin head -->
<head>
<title><?= Html::encode($this->title) ?> - <?= Html::encode(\Yii::$app->name) ?></title>
<meta charset="<?= \Yii::$app->charset ?>" />
<meta name="author" content="" />
<meta name="keywords" content=" yii2 extension payment baifubao" />
<meta name="description" content="yii2 extension, payment system, baifubao" />

<!-- begin ie modes -->
<meta http-equiv="x-ua-compatible" content="ie=edge" />
<meta name="renderer" content="webkit" />
<!-- end ie modes -->

<!-- begin csrf -->
<?= Html::csrfMetaTags() ?>
<!-- end csrf -->

<!-- begin baifubao -->
<meta name="VIP_BFB_PAYMENT" content="BAIFUBAO" />
<!-- end baifubao -->

<!-- begin static -->
<?php $this->head(); ?>
<!-- end static -->

</head>
<!-- end head -->

<!-- begin body -->
<body>
<?php $this->beginBody(); ?>

<?= $content ?>

<?php $this->endBody(); ?>
</body>
<!-- end body -->

</html>
<!-- end html -->
<?php $this->endPage(); ?>
