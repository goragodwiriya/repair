<?php
/**
 * @filesource modules/repair/controllers/export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Export;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * export.php
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * หน้าสำหรับพิมพ์ (print.html)
     *
     * @param Request $request
     */
    public function index(Request $request)
    {
        // session cookie
        $request->initSession();
        // can_received_repair, can_repair
        if (Login::checkPermission(Login::isMember(), array('can_received_repair', 'can_repair'))) {
            // ตรวจสอบ id ที่ต้องการ
            if (preg_match('/^([A-Za-z0-9\-_]{4,12})$/', $request->get('id')->toString(), $match)) {
                // อ่านข้อมูลการทำรายการ
                $index = \Repair\Export\Model::get($match[1]);
                if ($index) {
                    $detail = \Repair\Export\View::create()->render($index);
                }
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
