<?php
/**
 * @filesource modules/repair/views/receive.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Receive;

use Kotchasan\Date;
use Kotchasan\Html;

/**
 * module=repair-receive
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * เพิ่ม-แก้ไข ใบรับงาน
     *
     * @param object $index
     *
     * @return string
     */
    public function render($index)
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/repair/model/receive/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-tools',
            'title' => '{LNG_Details of} {LNG_Customer}'
        ));
        $groups = $fieldset->add('groups', array(
            'comment' => '{LNG_Find your transaction history by} {LNG_Name}, {LNG_Phone}'
        ));
        // name
        $groups->add('text', array(
            'id' => 'name',
            'labelClass' => 'g-input icon-customer',
            'itemClass' => 'width50',
            'label' => '{LNG_Name}',
            'placeholder' => '{LNG_Please fill in} {LNG_Name}',
            'maxlength' => 100,
            'value' => $index->name
        ));
        // phone
        $groups->add('text', array(
            'id' => 'phone',
            'labelClass' => 'g-input icon-phone',
            'itemClass' => 'width50',
            'label' => '{LNG_Phone}',
            'maxlength' => 32,
            'value' => $index->phone
        ));
        // address
        $fieldset->add('text', array(
            'id' => 'address',
            'labelClass' => 'g-input icon-address',
            'itemClass' => 'item',
            'label' => '{LNG_Address}',
            'maxlength' => 64,
            'value' => $index->address
        ));
        $groups = $fieldset->add('groups');
        // provinceID
        $groups->add('text', array(
            'id' => 'provinceID',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'width50',
            'label' => '{LNG_Province}',
            'datalist' => \Kotchasan\Province::all(),
            'value' => $index->provinceID
        ));
        // zipcode
        $groups->add('number', array(
            'id' => 'zipcode',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Zipcode}',
            'maxlength' => 10,
            'value' => $index->zipcode
        ));
        $fieldset = $form->add('fieldset', array(
            'title' => '{LNG_Repair job description}'
        ));
        $groups = $fieldset->add('groups', array(
            'comment' => '{LNG_Find your transaction history by} {LNG_Equipment}, {LNG_Serial/Registration No.}'
        ));
        // equipment
        $groups->add('text', array(
            'id' => 'equipment',
            'labelClass' => 'g-input icon-edit',
            'itemClass' => 'width50',
            'label' => '{LNG_Equipment}',
            'placeholder' => '{LNG_The name of the repairs, eg Computers}',
            'maxlength' => 64,
            'value' => $index->equipment
        ));
        // serial
        $groups->add('text', array(
            'id' => 'serial',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Serial/Registration No.}',
            'placeholder' => '{LNG_Identity of the repair machine used to separate items.}',
            'maxlength' => 20,
            'value' => $index->serial
        ));
        // inventory_id
        $fieldset->add('hidden', array(
            'id' => 'inventory_id',
            'value' => $index->inventory_id
        ));
        // job_description
        $fieldset->add('textarea', array(
            'id' => 'job_description',
            'labelClass' => 'g-input icon-file',
            'itemClass' => 'item',
            'label' => '{LNG_Problems and repairs details}',
            'rows' => 5,
            'value' => $index->job_description
        ));
        $groups = $fieldset->add('groups');
        // create_date
        $groups->add('date', array(
            'id' => 'create_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width33',
            'label' => '{LNG_Received date}',
            'value' => Date::format($index->create_date, 'o-m-d')
        ));
        // appointment_date
        $groups->add('date', array(
            'id' => 'appointment_date',
            'labelClass' => 'g-input icon-calendar',
            'itemClass' => 'width33',
            'label' => '{LNG_Appointment date}',
            'value' => $index->appointment_date
        ));
        // appraiser
        $groups->add('currency', array(
            'id' => 'appraiser',
            'labelClass' => 'g-input icon-money',
            'itemClass' => 'width33',
            'label' => '{LNG_Appraiser}',
            'value' => $index->appraiser
        ));
        // id
        $fieldset->add('hidden', array(
            'id' => 'id',
            'value' => $index->id
        ));
        // comment
        $fieldset->add('text', array(
            'id' => 'comment',
            'labelClass' => 'g-input icon-comments',
            'itemClass' => 'item',
            'label' => '{LNG_Comment}',
            'comment' => '{LNG_Note or additional notes}',
            'maxlength' => 255,
            'value' => $index->comment
        ));
        // status_id
        $fieldset->add('hidden', array(
            'id' => 'status_id',
            'value' => $index->status_id
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'id' => 'save',
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // save & print
        $fieldset->add('submit', array(
            'id' => 'save_print',
            'class' => 'button print large icon-print',
            'value' => '{LNG_Save &amp; Print receipt}'
        ));
        // print
        $fieldset->add('hidden', array(
            'id' => 'print',
            'value' => 0
        ));
        // Javascript
        $form->script('initRepairGet();');
        // คืนค่า HTML
        return $form->render();
    }
}
