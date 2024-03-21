<?php
/**
 * @filesource modules/repair/views/repair.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Repair;

use Kotchasan\Currency;
use Kotchasan\DataTable;
use Kotchasan\Date;
use Kotchasan\Language;
use Kotchasan\Province;
use Kotchasan\Template;

/**
 * module=repair-repair
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
     * repair.php
     *
     * @param object $index
     *
     * @return string
     */
    public function render($index)
    {
        // สถานะการซ่อม
        $this->statuses = \Repair\Status\Model::create();
        // ตาราง
        $table = new DataTable(array(
            'class' => 'border horiz-table',
            /* array datas */
            'datas' => \Repair\Detail\Model::getAllStatus($index->id),
            /* ปิดการใช้งาน Javascript */
            'enableJavascript' => false,
            /* ไม่แสดง caption */
            'showCaption' => false,
            /* ฟังก์ชั่นจัดรูปแบบการแสดงผลแถวของตาราง */
            'onRow' => array($this, 'onRow'),
            /* คอลัมน์ที่ไม่ต้องแสดงผล */
            'hideColumns' => array('id'),
            /* ส่วนหัวของตาราง และการเรียงลำดับ (thead) */
            'headers' => array(
                'name' => array(
                    'text' => '{LNG_Operator}',
                    'class' => 'nowrap'
                ),
                'status' => array(
                    'text' => '{LNG_Repair status}',
                    'class' => 'center nowrap'
                ),
                'cost' => array(
                    'text' => '{LNG_Cost}',
                    'class' => 'center nowrap'
                ),
                'create_date' => array(
                    'text' => '{LNG_Transaction date}',
                    'class' => 'center nowrap'
                ),
                'comment' => array(
                    'text' => '{LNG_Comment}'
                )
            ),
            /* รูปแบบการแสดงผลของคอลัมน์ (tbody) */
            'cols' => array(
                'name' => array(
                    'class' => 'nowrap'
                ),
                'status' => array(
                    'class' => 'center'
                ),
                'cost' => array(
                    'class' => 'right'
                ),
                'create_date' => array(
                    'class' => 'center nowrap'
                )
            )
        ));
        $logo = '';
        if (is_file(ROOT_PATH.DATA_FOLDER.'images/logo.png')) {
            $logo = '<img src="'.WEB_URL.DATA_FOLDER.'images/logo.png" alt="{WEBTITLE}">';
        }
        if ($logo == '' || !empty(self::$cfg->show_title_logo)) {
            $logo .= empty(self::$cfg->company_name) ? self::$cfg->web_title : self::$cfg->company_name;
        }
        // repair.html
        $template = Template::createFromFile(ROOT_PATH.'modules/repair/views/repair.html');
        $template->add(array(
            '/%LOGO%/' => $logo,
            '/%JOB_ID%/' => $index->job_id,
            '/%NAME%/' => $index->name,
            '/%PHONE%/' => $index->phone,
            '/%ADDRESS%/' => $index->address,
            '/%PROVINCE%/' => Province::get($index->provinceID),
            '/%ZIPCODE%/' => $index->zipcode,
            '/%EQUIPMENT%/' => $index->equipment,
            '/%SERIAL%/' => $index->serial,
            '/%JOB_DESCRIPTION%/' => nl2br($index->job_description),
            '/%CREATE_DATE%/' => Date::format($index->create_date, 'd M Y'),
            '/%APPOINTMENT_DATE%/' => Date::format($index->appointment_date, 'd M Y'),
            '/%APPRAISER%/' => empty($index->appraiser) ? '' : Currency::format($index->appraiser),
            '/%COMMENT%/' => $index->comment,
            '/%DETAILS%/' => $table->render(),
            '/{LANGUAGE}/' => Language::name(),
            '/{WEBURL}/' => WEB_URL,
            '/%BG_COLOR%/' => self::$cfg->header_bg_color,
            '/%COLOR%/' => self::$cfg->header_color,
            '/%CURRENCY_UNIT%/' => Language::get('CURRENCY_UNITS', '', self::$cfg->currency_unit)
        ));
        // คืนค่า HTML
        return Language::trans($template->render());
    }

    /**
     * จัดรูปแบบการแสดงผลในแต่ละแถว.
     *
     * @param array $item
     *
     * @return array
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
