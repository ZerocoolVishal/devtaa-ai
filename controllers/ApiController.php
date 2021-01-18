<?php

namespace app\controllers;

use app\helpers\AppHelper;
use app\models\BookExpertVisit;
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
            'id' => (string)$model->user_id,
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

    public function actionBookExpertVisit() {

        $request = Yii::$app->request->bodyParams;

        if (empty($request)) {
            $this->response_code = 500;
            $this->message = 'There was an error processing the request. Please try again later.';
        }

        $model = new BookExpertVisit();
        $model->user_id = $request['user_id'];
        $model->society_name = $request['society_name'];
        $model->society_type = $request['society_type'];
        $model->society_address = $request['society_address'];
        $model->contact_name = $request['contact_name'];
        $model->contact_phone = $request['contact_phone'];
        $model->contact_designation = $request['contact_designation'];
        $model->status = 1;
        $model->expert_name = 'Divyang Abhyankar';
        $model->expert_phone = '+91 79725 92726';

        $book_date = date('Y:m:d H:i:s');
        $visit_date = date('Y-m-d', strtotime($book_date . ' +1 day'));

        $model->visit_date = $visit_date;
        $model->created_at = $book_date;

        if($model->save()) {
            $booked_visits = BookExpertVisit::find()
                ->where(['user_id' => $model->user_id])
                ->orderBy(['created_at' => SORT_DESC])
                ->all();
            $this->response_code = 200;
            $this->message = 'Visit Booked';
            $this->data = $booked_visits;
        }

        return $this->sendResponse();

    }

    public function actionExpertVisitHistory($user_id) {

        $model = BookExpertVisit::find()
            ->where(['user_id' => $user_id])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $this->response_code = 200;
        $this->message = 'Success';
        $this->data = $model;

        return $this->sendResponse();
    }

}
