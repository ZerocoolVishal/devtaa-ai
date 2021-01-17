<?php

namespace app\controllers;

use app\helpers\AppHelper;
use app\models\User;
use app\models\Users;
use Yii;
use yii\web\Response;

class ApiController extends \yii\web\Controller
{

    private $response_code = 200;
    private $message = "Success";
    private $data;

    public function init()
    {
        $headers = Yii::$app->response->headers;
        $headers->add("Cache-Control", "no-cache, no-store, must-revalidate");
        $headers->add("Pragma", "no-cache");
        $headers->add("Expires", 0);
        parent::init();
    }

    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    // restrict access to
                    'Origin' => ['http://localhost:4200', 'http://3.137.151.134', 'http://dais.vesolutions.in', 'https://dais.vesolutions.in', 'http://vesolutions.in', 'https://vesolutions.in'],
                    // Allow only POST and PUT methods
                    'Access-Control-Request-Method' => ['GET', 'HEAD', 'POST', 'PUT'],
                    // Allow only headers 'X-Wsse'
                    'Access-Control-Request-Headers' => ['X-Wsse', 'Content-Type'],
                    // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
                    'Access-Control-Allow-Credentials' => true,
                    // Allow OPTIONS caching
                    'Access-Control-Max-Age' => 3600,
                    // Allow the X-Pagination-Current-Page header to be exposed to the browser.
                    'Access-Control-Expose-Headers' => ['X-Pagination-Current-Page'],
                ],
            ],
        ];
    }

    public function beforeAction($action)
    {
        Yii::$app->controller->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    private function sendResponse() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return [
            'status' => $this->response_code,
            'message' => $this->message,
            'data' => $this->data
        ];
    }

    public function actionIndex()
    {
        return $this->sendResponse();
    }

    private function getUser(Users $model)
    {
        return [
            'user_id' => (string)$model->user_id,
            'name' => $model->name,
            'email' => (string)$model->email,
            'phone' => (string)$model->phone,
        ];
    }

    public function actionSocialRegister()
    {
        $request = Yii::$app->request->bodyParams;
        if (!empty($request)) {
            $model = \app\models\Users::find()
                ->where(['email' => strtolower($request['email']), 'is_deleted' => 0, 'is_active' => 1])
                ->one();

            if (empty($model)) {
                $model = new \app\models\Users();
                $model->name = $request['name'];
                $model->email = strtolower($request['email']);
                $model->created_at = date('Y-m-d H:i:s');
                $randomString = Yii::$app->security->generateRandomString(6);
                $model->password = Yii::$app->security->generatePasswordHash($randomString);
                $model->is_email_verified = 1;
            }

            if (!empty($request['social_register_type'])) {
                $model->social_register_type = $request['social_register_type'];
            }

            $model->is_social_register = 1;
            $model->is_active = 1;

            if ($model->save()) {
                $this->response_code = 200;
                $this->message = 'User registered successfully';
                $this->data = $this->getUser($model);
            } else {
                $this->response_code = 201;
                $this->message = 'Fail to register an user';
            }
        } else {
            $this->response_code = 500;
            $this->message = 'There was an error processing the request. Please try again later.';
        }
        return $this->sendResponse();
    }

}
