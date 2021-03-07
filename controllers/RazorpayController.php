<?php

namespace app\controllers;

use app\models\Payments;
use Razorpay\Api\Api;
use Yii;
use yii\web\Response;

class RazorpayController extends \yii\web\Controller
{
    public function actionIndex()
    {
        return $this->render('index');
    }

}
