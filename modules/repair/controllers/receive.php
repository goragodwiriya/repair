<?php
/**
 * @filesource modules/repair/controllers/receive.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Receive;

use Gcms\Login;
use Kotchasan\Html;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=repair-receive
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * เพิ่ม-แก้ไข ใบรับงาน
     *
     * @param Request $request
     *
     * @return string
     */
    public function render(Request $request)
    {
        // สมาชิก
        $login = Login::isMember();
        // อ่านข้อมูลรายการที่ต้องการ
        $index = \Repair\Receive\Model::get($request->request('id')->toInt());
        // ข้อความ title bar
        $this->title = Language::get($index->id == 0 ? 'Get a repair' : 'Repair job description');
        // เลือกเมนู
        $this->menu = 'repair';
        // สามารถรับเครื่องซ่อมได้
        if (Login::checkPermission($login, 'can_received_repair')) {
            // แสดงผล
            $section = Html::create('section');
            // breadcrumbs
            $breadcrumbs = $section->add('nav', array(
                'class' => 'breadcrumbs'
            ));
            $ul = $breadcrumbs->add('ul');
            $ul->appendChild('<li><span class="icon-tools">{LNG_Repair system}</span></li>');
            $ul->appendChild('<li><a href="{BACKURL?module=repair-setup&id=0}">{LNG_Repair list}</a></li>');
            $ul->appendChild('<li><span>{LNG_'.($index->id == 0 ? 'Add' : 'Edit').'}</span></li>');
            $section->add('header', array(
                'innerHTML' => '<h2 class="icon-write">'.$this->title.'</h2>'
            ));
            $div = $section->add('div', array(
                'class' => 'content_bg'
            ));
            // แสดงฟอร์ม
            $div->appendChild(\Repair\Receive\View::create()->render($index));
            // คืนค่า HTML
            return $section->render();
        }
        // 404
        return \Index\Error\Controller::execute($this, $request->getUri());
    }
}
