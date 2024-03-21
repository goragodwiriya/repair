<?php
/**
 * @filesource modules/repair/controllers/repair.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Repair;

use Kotchasan\Http\Request;

/**
 * Controller สำหรับแสดงหน้าเว็บ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * หน้าสำหรับแสดงรายละเอียดการซ่อมสำหรับลูกค้า
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        if (preg_match('/^([A-Za-z0-9\-_]{4,12})$/', $request->get('id')->toString(), $match)) {
            // session cookie
            $request->initSession();
            // อ่านข้อมูลการทำรายการ
            $index = \Repair\Export\Model::get($match[1]);
            if ($index) {
                $detail = \Repair\Repair\View::create()->render($index);
            }
        }
        if (empty($detail)) {
            // ไม่พบโมดูลหรือไม่มีสิทธิ
            new \Kotchasan\Http\NotFound();
        } else {
            // แสดงผล
            echo $detail;
        }
    }
}
