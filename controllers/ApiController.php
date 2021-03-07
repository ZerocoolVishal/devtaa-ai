<?php

namespace app\controllers;

use app\helpers\AppHelper;
use app\helpers\RazorpayHelpers;
use app\models\BookExpertVisit;
use app\models\FeasibilityReport;
use app\models\Meeting;
use app\models\Payments;
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
        $this->data = $model;

        return $this->sendResponse();
    }

    public function actionBookMeeting() {

        $request = Yii::$app->request->bodyParams;

        $meeting = new Meeting();
        $meeting->user_id = $request['user_id'];
        $meeting->society_name = $request['society_name'];
        $meeting->contact_name = $request['contact_name'];
        $meeting->contact_phone = $request['contact_phone'];
        $meeting->contact_designation = $request['contact_designation'];
        $meeting->status = 0;
        $meeting->visit_date = $request['visit_date'];
        $meeting->created_at = date('Y-m-d H:i:s');
        $meeting->amount = 25000;

        if ($meeting->save()) {

            $model = Meeting::find()
                ->where(['user_id' => $request['user_id']])
                ->all();
            $this->data = $model;
            $this->message = 'Meeting Booked';

            return $this->sendResponse();
        }

        return $this->sendResponse();
    }

    public function actionShowBookedMeeting($user_id) {

        $model = Meeting::find()
            ->where(['user_id' => $user_id])
            ->orderBy(['meeting_id' => SORT_DESC])
            ->all();

        $this->response_code = 200;
        $this->data = $model;

        return $this->sendResponse();
    }

    public function actionFreeFeasibilityReport() {

        $request = Yii::$app->request->bodyParams;

        $feasibilityReport = [
            'FeasibilityReport' => $request
        ];

        $model = new FeasibilityReport();
        $model->load($feasibilityReport);
        $model->created_at = date('Y-m-d H:i:s');

        if($model->save()) {

            $reports = FeasibilityReport::find()
                ->where(['user_id' => $model->user_id, 'is_paid' => 0])
                ->orderBy(['feasibility_report_id' => SORT_DESC])
                ->all();

            $this->message = "Feasibility report generated successfully";
            $this->response_code = 200;
            $this->data = $reports;
        }
        else {
            $this->message = "Internal service error";
            $this->response_code = 500;
            $this->data = $model->getErrors();
        }

        return $this->sendResponse();
    }

    public function actionFreeFeasibilityReportHistory($user_id) {

        $reports = FeasibilityReport::find()
            ->where(['user_id' => $user_id, 'is_paid' => 0])
            ->orderBy(['feasibility_report_id' => SORT_DESC])
            ->all();

        $this->response_code = 200;
        $this->data = $reports;

        return $this->sendResponse();
    }

    public function actionFreeFeasibilityReportDetails($user_id, $feasibility_report_id) {

        $report = FeasibilityReport::find()
            ->where(['user_id' => $user_id, 'feasibility_report_id' => $feasibility_report_id])
            ->asArray()
            ->one();

        if (empty($report)) {

            $this->response_code = 404;
            $this->message = "report not found";
            $this->data = $report;

            return $this->sendResponse();
        }

        $part_1['plot_area'] = $report['plot_size'];
        $part_1['road_width'] = 13.40;

        $deduction_for['road_set_back_area'] = 0;
        $deduction_for['proposed_dp_road'] = 0;
        $deduction_for['any_reservation'] = 0;
        $deduction_for['total'] = $deduction_for['road_set_back_area'] + $deduction_for['proposed_dp_road'] + $deduction_for['any_reservation'];

        $part_1['deduction_for'] = $deduction_for;
        $part_1['balance_area_of_plot'] = $part_1['plot_area'] - $deduction_for['total'];
        $part_1['net_area_of_plot'] = $part_1['balance_area_of_plot'];

        /* FSI PERMISSIBLE  as per Reg.No.30, Table 12 of DCPR 30. */
        $fsi_permissible['zonal_basic_fsi'] = 1;
        $fsi_permissible['zonal_basic_fsi_area'] = $part_1['net_area_of_plot'] * $fsi_permissible['zonal_basic_fsi'];
        $fsi_permissible['additional_fsi'] = 0.50;
        $fsi_permissible['additional_fsi_area'] = $part_1['net_area_of_plot'] * $fsi_permissible['additional_fsi'];
        $fsi_permissible['admissible_tdr'] = 0.70; // TODO: INPUT
        $fsi_permissible['admissible_tdr_area'] = $part_1['net_area_of_plot'] * $fsi_permissible['admissible_tdr'];

        $fsi_permissible['maximum_fsi_cap'] =  $fsi_permissible['zonal_basic_fsi'] + $fsi_permissible['additional_fsi'] + $fsi_permissible['admissible_tdr'];
        $fsi_permissible['total'] = $fsi_permissible['zonal_basic_fsi_area'] + $fsi_permissible['additional_fsi_area'] + $fsi_permissible['admissible_tdr_area'];

        $part_1['fsi_permissible'] = $fsi_permissible;

        /* FSI PERMISSIBLE AS PER REG.33(7)(B) */
        $fsi_permissible_2['exist_authorized_b_u_area'] = $report['existing_built_up_area']; // TODO: INPUT
        $fsi_permissible_2['incentive_add_b_u_area'] = 6996.60;
        $fsi_permissible_2['total'] = $fsi_permissible_2['exist_authorized_b_u_area'] + $fsi_permissible_2['incentive_add_b_u_area'];
        $part_1['fsi_permissible_2'] = $fsi_permissible_2;

        /* BALANCE B/U AREA THAT MAY AVAIL BY FSI BY CHARGING PREMIUM/PURCHASING TDR */
        $part_1['balance_b_u_area'] = $fsi_permissible['total'] - $fsi_permissible_2['total'];

        /* PERM. B/U AREA AS PER REG 33(7)(B) */
        $part_1['perm_b_u_area'] = $part_1['balance_b_u_area'] + $fsi_permissible_2['total'];

        /* FUNGIBLE FSI As per Reg.No.31(3) of DCPR 2034 (FUNGIBLE FSI 35% ) */
        $part_1['fungible_fsi'] = $part_1['perm_b_u_area'] / 100 * 35;

        /* TOTAL PERMISSIBLE BUILT UP AREA including Fungible */
        $part_1['total_permissible_built_up_area_including_fungible'] = $part_1['perm_b_u_area'] + $part_1['fungible_fsi'];

        /* RERA CARPET AREA (92%) */
        $part_1['rera_carpet_area'] = $part_1['total_permissible_built_up_area_including_fungible'] / 100 * 92;

        $note['fsi_by_charging_premium'] = $fsi_permissible['additional_fsi_area'];
        $note['tdr_to_be_purchased_from_open_market'] = $part_1['balance_b_u_area'] - $note['fsi_by_charging_premium'];
        $note['existing_built_up_area_as_per_society'] = $fsi_permissible_2['exist_authorized_b_u_area'];

        /* FREE FUNGIBLE 35% OF EXISTING B/U AREA */
        $note['free_fungible'] = $note['existing_built_up_area_as_per_society'] / 100 * 35;
        /* FUNGIBLE BY CHARGING PREMIUM = Total permissible fungible (8) LESS Free fungible  */
        $note['fungible_by_charging_premium'] = $part_1['fungible_fsi'] +  $part_1['perm_b_u_area'];

        /* Existing members (Residential)  */
        $note['existing_members_residential'] = $report['no_of_tenants'];

        /* Existing members (Commercial)  */
        $note['existing_members_commercial'] = 0;

        /* AS PER DCPR 33(7)(B) Additional FSI  = 15% OF Existing Builtup area OR 10.00Sq.M. per existing member; whichever is greater */
        $note['additional_fsi_as_dcpr'] = $note['existing_members_residential'] * 10;

        /* EXISTING CARPET AREA STATEMENT AS PER SOCIETY DOC'S */

        $note['total_existing_carpet_area'] = $report['area_currently_consumed'];

        /* CONSTRUCTION AREA  */

        /* CONSTRUCTION OF NET BUILT-UP AREA */
        $note['construction_of_net_built_up_area'] = $part_1['total_permissible_built_up_area_including_fungible'];

        /* CONSTRUCTION AREA OF STAIRCASE AND LIFT (25%) */
        $note['construction_area_of_staircase_and_lift'] = $note['construction_of_net_built_up_area'] / 100 * 25;

        /* CONSTRUCTION AREA FOR PARKING (25%) */
        $note['construction_area_for_parking'] = $note['construction_of_net_built_up_area'] / 100 * 25;

        /* TOTAL CONSTRUCTION AREA */
        $note['total_construction_area'] = $note['construction_of_net_built_up_area'] + $note['construction_area_of_staircase_and_lift'] +  $note['construction_area_for_parking'];

        $part_1['note'] = $note;

        /* ADDITIONAL AREA */
        $additional_area['additional_area'] = $part_1['total_permissible_built_up_area_including_fungible'] - $part_1['net_area_of_plot'];
        $additional_area['slum'] =  $note['tdr_to_be_purchased_from_open_market'];
        $additional_area['gen_tdr_and_incentive'] = $fsi_permissible['admissible_tdr_area'] - $additional_area['slum'];
        $additional_area['FSI_0_50'] = $note['fsi_by_charging_premium'];
        $additional_area['fungible'] = $part_1['fungible_fsi'];
        $additional_area['total'] = $additional_area['slum'] + $additional_area['gen_tdr_and_incentive'] + $additional_area['FSI_0_50'] + $additional_area['fungible'];

        $additional_area['additional_area_sqm'] = $additional_area['additional_area'] / 10.764;

        /* PERCENTAGE OF ADDITIONAL FSI */
        $additional_area['slum_percentage'] = 100 * $additional_area['slum'] / $additional_area['additional_area'];
        $additional_area['gen_tdr_and_incentive_percentage'] = 100 * $additional_area['gen_tdr_and_incentive'] / $additional_area['additional_area'];
        $additional_area['FSI_0_50_percentage'] = 100 * $additional_area['FSI_0_50'] / $additional_area['additional_area'];
        $additional_area['fungible_percentage'] = 100 * $additional_area['fungible'] / $additional_area['additional_area'];
        $additional_area['total_percentage'] = $additional_area['slum_percentage'] + $additional_area['gen_tdr_and_incentive_percentage'] + $additional_area['FSI_0_50_percentage'] + $additional_area['fungible_percentage'];


        $part_1['additional_area'] = $additional_area;

        /* DEFICIENT AREA */
        /* Note:- At present 25% area of net built up area is considered as Deficient area */
        $deficient_area['deficient_area'] = $part_1['total_permissible_built_up_area_including_fungible'] / 100 * 25;
        $deficient_area['deficient_area_sqm'] = $deficient_area['deficient_area'] / 10.764;
        /* RATE */
        $deficient_area['rr_rate'] = $report['residential_redirecionar_rate']; //TODO: Calculate
        /* R.R.RATE x 25% */
        $deficient_area['rr_rate_25'] = $deficient_area['rr_rate'] / 4;

        /* Deficient area as per percentage */
        $deficient_area['slum_percentage'] = $deficient_area['deficient_area_sqm'] / 100 * $additional_area['slum_percentage'];
        $deficient_area['gen_tdr_and_incentive_percentage'] = $deficient_area['deficient_area_sqm'] / 100 * $additional_area['gen_tdr_and_incentive_percentage'];
        $deficient_area['FSI_0_50_percentage'] = $deficient_area['deficient_area_sqm'] / 100 * $additional_area['FSI_0_50_percentage'];
        $deficient_area['fungible_percentage'] = $deficient_area['deficient_area_sqm'] / 100 * $additional_area['fungible_percentage'];
        $deficient_area['total_percentage'] = $deficient_area['slum_percentage'] + $deficient_area['gen_tdr_and_incentive_percentage'] + $deficient_area['FSI_0_50_percentage'] + $deficient_area['fungible_percentage'];

        /* Deficient AMOUNT */
        $deficient_area['amount']['slum_amount'] = $deficient_area['rr_rate_25'] * $deficient_area['slum_percentage'];
        $deficient_area['amount']['gen_tdr_and_incentive_amount'] = $deficient_area['rr_rate_25'] * $deficient_area['gen_tdr_and_incentive_percentage'];
        $deficient_area['amount']['FSI_0_50_amount'] = $deficient_area['rr_rate_25'] * $deficient_area['FSI_0_50_percentage'];
        $deficient_area['amount']['fungible_amount'] = $deficient_area['rr_rate_25'] * $deficient_area['fungible_percentage'];

        /* Deficient AMOUNT AS PER PERCENTAGE */
        $deficient_area['amount_as_per_percentage']['slum'] = $deficient_area['amount']['slum_amount'] / 100 * 10;
        $deficient_area['amount_as_per_percentage']['gen_tdr_and_incentive'] = $deficient_area['amount']['gen_tdr_and_incentive_amount'] / 100 * 100;
        $deficient_area['amount_as_per_percentage']['FSI_0_50'] = $deficient_area['amount']['FSI_0_50_amount'] / 100 * 100;
        $deficient_area['amount_as_per_percentage']['fungible'] = $deficient_area['amount']['fungible_amount'] / 100 * 25;
        $deficient_area['amount_as_per_percentage']['total'] =
            $deficient_area['amount_as_per_percentage']['slum']
            + $deficient_area['amount_as_per_percentage']['gen_tdr_and_incentive']
            + $deficient_area['amount_as_per_percentage']['FSI_0_50']
            + $deficient_area['amount_as_per_percentage']['fungible'];

        /* Deficient Premium as per Telescopic method by adding 20% */
        $deficient_area['deficient_premium'] = $deficient_area['amount_as_per_percentage']['total'] * 1.2;

        $part_1['deficient_area'] = $deficient_area;

        $report['part_1'] = $part_1;

        $this->response_code = 200;
        $this->data = $report;

        return $this->sendResponse();
    }

    public function actionFeasibilityReport() {

        $request = Yii::$app->request->bodyParams;

        $feasibilityReport = [
            'FeasibilityReport' => $request
        ];

        $model = new FeasibilityReport();
        $model->load($feasibilityReport);
        $model->is_paid = FeasibilityReport::PAYMENT_INIT;
        $model->is_payment_processed = 1;
        $model->created_at = date('Y-m-d H:i:s');

        if($model->save()) {

            $reports = FeasibilityReport::find()
                ->where([
                    'user_id' => $model->user_id,
                    'is_paid' => 1,
                    'is_payment_processed' => FeasibilityReport::PAYMENT_SUCCESS
                ])
                ->orderBy(['feasibility_report_id' => SORT_DESC])
                ->all();

            $payment = new Payments();
            $payment->payment_type = Payments::FEASIBILITY_REPORT;
            $payment->payment_type_id = $model->feasibility_report_id;
            $payment->total_amount = Yii::$app->params['FEASIBILITY_REPORT_COST'];
            $payment->payment_date = date('Y-m-d H:i:s');
            $payment->is_processed = 0;
            $payment->currency_code = Yii::$app->params['displayCurrency'];
            $payment->save();

            $paymentDetails = RazorpayHelpers::pay(
                $payment->payment_id,
                $payment->total_amount,
                'Feasibility Report',
                'Devtaa A.I Services',
                $model->user->name,
                $model->user->email,
                $model->user->phone,
                $payment->currency_code
            );

            $this->message = "Feasibility report generated successfully";
            $this->response_code = 200;
            $this->data = [
                'reports' => $reports,
                'paymentDetails' => $paymentDetails
            ];
        }
        else {
            $this->message = "Internal service error";
            $this->response_code = 500;
            $this->data = $model->getErrors();
        }

        return $this->sendResponse();

    }

    public function actionFeasibilityReportHistory($user_id) {

        $reports = FeasibilityReport::find()
            ->where([
                'user_id' => $user_id,
                'is_paid' => 1,
                'is_payment_processed' => FeasibilityReport::PAYMENT_SUCCESS
            ])
            ->orderBy(['feasibility_report_id' => SORT_DESC])
            ->all();

        $this->response_code = 200;
        $this->data = $reports;

        return $this->sendResponse();
    }

}
