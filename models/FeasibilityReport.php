<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tbl_feasibility_report".
 *
 * @property int $feasibility_report_id
 * @property string|null $cts_no
 * @property string|null $ward
 * @property string|null $village
 * @property string|null $plot_size
 * @property string|null $residential_redirecionar_rate
 * @property int|null $no_of_tenants
 * @property string|null $area_currently_consumed
 * @property string|null $society_name
 * @property string|null $society_type
 * @property string|null $society_address
 * @property string|null $contact_name
 * @property string|null $contact_phone
 * @property string|null $contact_designation
 * @property string|null $created_at
 */
class FeasibilityReport extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tbl_feasibility_report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cts_no', 'ward', 'village', 'plot_size', 'residential_redirecionar_rate', 'area_currently_consumed', 'society_name', 'society_type', 'society_address', 'contact_name', 'contact_phone', 'contact_designation', 'no_of_tenants'], 'required'],
            [['no_of_tenants'], 'integer'],
            [['contact_phone'], 'integer', 'message' => 'Please enter your 10 digit mobile no.'],
            [['created_at'], 'safe'],
            [['plot_size', 'residential_redirecionar_rate', 'area_currently_consumed'], 'number'],
            [['cts_no', 'ward', 'village', 'society_name', 'society_type', 'society_address', 'contact_name', 'contact_designation'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'feasibility_report_id' => 'Feasibility Report ID',
            'cts_no' => 'CTS No.',
            'ward' => 'Ward',
            'village' => 'Village',
            'plot_size' => 'Plot Size (sqmt)',
            'residential_redirecionar_rate' => 'Residential Redirecionar Rate (per sqmt)',
            'no_of_tenants' => 'No of Tenants',
            'area_currently_consumed' => 'Area Currently Consumed',
            'society_name' => 'Society Name',
            'society_type' => 'Society Type',
            'society_address' => 'Society Address',
            'contact_name' => 'Contact Name',
            'contact_phone' => 'Contact Phone',
            'contact_designation' => 'Contact Designation',
            'created_at' => 'Created At',
        ];
    }
}
