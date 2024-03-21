<?php
/**
 * @filesource modules/repair/views/settings.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Settings;

use Kotchasan\Html;
use Kotchasan\Language;

/**
 * module=repair-settings
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * ตั้งค่าโมดูล
     *
     * @return string
     */
    public function render()
    {
        $form = Html::create('form', array(
            'id' => 'setup_frm',
            'class' => 'setup_frm',
            'autocomplete' => 'off',
            'action' => 'index.php/repair/model/settings/submit',
            'onsubmit' => 'doFormSubmit',
            'ajax' => true,
            'token' => true
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-config',
            'title' => '{LNG_Module settings}'
        ));
        // repair_first_status
        $fieldset->add('select', array(
            'id' => 'repair_first_status',
            'labelClass' => 'g-input icon-tools',
            'itemClass' => 'item',
            'label' => '{LNG_Initial repair status}',
            'options' => \Repair\Status\Model::create()->toSelect(),
            'value' => isset(self::$cfg->repair_first_status) ? self::$cfg->repair_first_status : 1
        ));
        $comment = '{LNG_Prefix, if changed The number will be counted again. You can enter %Y%M (year, month).}';
        $comment .= ', {LNG_Number such as %04d (%04d means 4 digits, maximum 11 digits)}';
        $groups = $fieldset->add('groups', array(
            'comment' => $comment
        ));
        // repair_prefix
        $groups->add('text', array(
            'id' => 'repair_prefix',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Prefix}',
            'placeholder' => 'JOB%Y%M-',
            'value' => isset(self::$cfg->repair_prefix) ? self::$cfg->repair_prefix : ''
        ));
        // repair_job_no
        $groups->add('text', array(
            'id' => 'repair_job_no',
            'labelClass' => 'g-input icon-number',
            'itemClass' => 'width50',
            'label' => '{LNG_Receipt No.}',
            'placeholder' => '%04d, JOB%04d',
            'value' => isset(self::$cfg->repair_job_no) ? self::$cfg->repair_job_no : 'JOB%04d'
        ));
        $fieldset->add('div', array(
            'class' => 'warning item',
            'innerHTML' => '{LNG_Warning, specifying a number code format May allow users to guess the number of the repair receipt. and can pretend should be used with caution (recommended to use randomly)}'
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-office',
            'title' => '{LNG_Details of} {LNG_Company}'
        ));
        $groups = $fieldset->add('groups');
        // company_name
        $groups->add('text', array(
            'id' => 'company_name',
            'labelClass' => 'g-input icon-user',
            'itemClass' => 'width50',
            'label' => '{LNG_Company name}',
            'comment' => '%COMPANY%',
            'value' => isset(self::$cfg->company_name) ? self::$cfg->company_name : ''
        ));
        // phone
        $groups->add('text', array(
            'id' => 'phone',
            'labelClass' => 'g-input icon-phone',
            'itemClass' => 'width50',
            'label' => '{LNG_Phone}',
            'maxlength' => 32,
            'comment' => '%COMPANYPHONE%',
            'value' => isset(self::$cfg->phone) ? self::$cfg->phone : ''
        ));
        // address
        $fieldset->add('text', array(
            'id' => 'address',
            'labelClass' => 'g-input icon-location',
            'itemClass' => 'item',
            'label' => '{LNG_Address}',
            'comment' => '%COMPANYADDRESS%',
            'value' => isset(self::$cfg->address) ? self::$cfg->address : ''
        ));
        $fieldset = $form->add('fieldset', array(
            'titleClass' => 'icon-money',
            'title' => '{LNG_Other}'
        ));
        // currency_unit
        $fieldset->add('select', array(
            'id' => 'currency_unit',
            'labelClass' => 'g-input icon-currency',
            'itemClass' => 'item',
            'label' => '{LNG_Currency unit}',
            'options' => Language::get('CURRENCY_UNITS'),
            'value' => isset(self::$cfg->currency_unit) ? self::$cfg->currency_unit : 'THB'
        ));
        $fieldset = $form->add('fieldset', array(
            'class' => 'submit'
        ));
        // submit
        $fieldset->add('submit', array(
            'class' => 'button save large icon-save',
            'value' => '{LNG_Save}'
        ));
        // คืนค่า HTML
        return $form->render();
    }
}
