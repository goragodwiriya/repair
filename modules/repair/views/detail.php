<?php
/**
 * @filesource modules/repair/views/detail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Detail;

use Kotchasan\Currency;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Language;
use Kotchasan\Province;
use Kotchasan\Template;

/**
 * module=repair-detail
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * @var mixed
     */
    private $statuses;

    /**
     * แสดงรายละเอียดการซ่อม
     *
     * @param object $index
     * @param array $login
     *
     * @return string
     */
    public function render($index, $login)
    {
        // สถานะการซ่อม
        $this->statuses = \Repair\Status\Model::create();
        // อ่านสถานะการทำรายการทั้งหมด
        $statuses = \Repair\Detail\Model::getAllStatus($index->id);
        // URL สำหรับส่งให้ตาราง
        $uri = self::$request->createUriWithGlobals(WEB_URL.'index.php');
        // ตาราง
        $table = new DataTable(array(
            /* Uri */
            'uri' => $uri,
            /* array datas */
            'datas' => $statuses,
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* ตั้งค่าการกระทำของของตัวเลือกต่างๆ ด้านล่างตาราง ซึ่งจะใช้ร่วมกับการขีดถูกเลือกแถว */
            'action' => 'index.php/repair/model/detail/action?repair_id='.$index->id,
            'actionCallback' => 'dataTableActionCallback',
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'name' => array(
                    'text' => '{LNG_Operator}'
                ),
                'status' => array(
                    'text' => '{LNG_Repair status}',
                    'class' => 'center'
                ),
                'cost' => array(
                    'text' => '{LNG_Cost}',
                    'class' => 'center'
                ),
                'create_date' => array(
                    'text' => '{LNG_Transaction date}',
                    'class' => 'center'
                ),
                'comment' => array(
                    'text' => '{LNG_Comment}'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'status' => array(
                    'class' => 'center'
                ),
                'cost' => array(
                    'class' => 'right'
                ),
                'create_date' => array(
                    'class' => 'center'
                )
            ),
            /* ปุ่มแสดงในแต่ละแถว */
            'buttons' => array(
                'delete' => array(
                    'class' => 'icon-delete button red notext',
                    'id' => ':id',
                    'title' => '{LNG_Delete}'
                )
            )
        ));
        // template
        $template = Template::createFromFile(ROOT_PATH.'modules/repair/views/detail.html');
        $template->add(array(
            '/%NAME%/' => $index->name,
            '/%PHONE%/' => $index->phone,
            '/%ADDRESS%/' => $index->address,
            '/%PROVINCE%/' => Province::get($index->provinceID),
            '/%ZIPCODE%/' => $index->zipcode,
            '/%TOPIC%/' => $index->equipment,
            '/%PRODUCT_NO%/' => $index->serial,
            '/%JOB_DESCRIPTION%/' => nl2br($index->job_description),
            '/%CREATE_DATE%/' => Date::format($index->create_date, 'd M Y'),
            '/%APPOINTMENT_DATE%/' => Date::format($index->appointment_date, 'd M Y'),
            '/%APPRAISER%/' => empty($index->appraiser) ? '&nbsp;' : Currency::format($index->appraiser),
            '/%COMMENT%/' => $index->comment,
            '/%DETAILS%/' => $table->render(),
            '/%CURRENCY_UNIT%/' => Language::get('CURRENCY_UNITS', '', self::$cfg->currency_unit)
        ));
        // คืนค่า HTML
        return $template->render();
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว
     *
     * @param array  $item ข้อมูลแถว
     * @param int    $o    ID ของข้อมูล
     * @param object $prop กำหนด properties ของ TR
     *
     * @return array คืนค่า $item กลับไป
     */
    public function onRow($item, $o, $prop)
    {
        $item['cost'] = $item['cost'] == 0 ? '' : Currency::format($item['cost']);
        $item['comment'] = nl2br($item['comment']);
        $item['create_date'] = Date::format($item['create_date'], 'd M Y H:i');
        $item['status'] = '<mark class=term style="background-color:'.$this->statuses->getColor($item['status']).'">'.$this->statuses->get($item['status']).'</mark>';
        return $item;
    }
}
