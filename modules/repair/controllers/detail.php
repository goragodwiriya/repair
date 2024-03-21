<?php
/**
 * @filesource modules/repair/controllers/detail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Detail;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=repair-detail
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * รายละเอียดการซ่อม
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // อ่านข้อมูลรายการที่ต้องการ
        $index = \Repair\Detail\Model::get($request->request('id')->toInt());
        // ข้อความ title bar
        $this->title = Language::get('Repair job description');
        // เลือกเมนู
        $this->menu = 'repair';
        // สมาชิก
        $login = Login::isMember();
        // สามารถรับเครื่องซ่อมได้
        if ($index && Login::checkPermission($login, array('can_received_repair', 'can_repair'))) {
            // ข้อความ title bar
            $this->title .= ' '.$index->job_id;
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-tools">{LNG_Repair system}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=repair-setup&id=0}">{LNG_Repair list}</a></li>');
            $ul->appendChild('<li><span>'.$index->job_id.'</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-file">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Repair\Detail\View::create()->render($index, $login));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
