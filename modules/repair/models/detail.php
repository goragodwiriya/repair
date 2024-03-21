<?php
/**
 * @filesource modules/repair/models/detail.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Detail;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * module=repair-detail
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านรายละเอียดการทำรายการจาก $id
     *
     * @param int $id
     *
     * @return object
     */
    public static function get($id)
    {
        $q1 = static::createQuery()
            ->select(Sql::MAX('id'))
            ->from('repair_status')
            ->where(array('repair_id', 'R.id'));
        return static::createQuery()
            ->from('repair R')
            ->join('inventory V', 'LEFT', array('V.id', 'R.inventory_id'))
            ->join('repair_status S', 'LEFT', array(array('S.repair_id', 'R.id'), array('S.id', $q1)))
            ->where(array('R.id', $id))
            ->first('R.*', 'V.equipment', 'V.serial', 'S.status', 'S.comment', 'S.cost', 'S.operator_id', 'S.id status_id');
    }

    /**
     * อ่านสถานะการทำรายการทั้งหมด
     *
     * @param int $id
     *
     * @return array
     */
    public static function getAllStatus($id)
    {
        return static::createQuery()
            ->select('S.id', 'U.name', 'S.status', 'S.cost', 'S.create_date', 'S.comment')
            ->from('repair_status S')
            ->join('user U', 'LEFT', array('U.id', 'S.operator_id'))
            ->where(array('S.repair_id', $id))
            ->order('S.id')
            ->toArray()
            ->execute();
    }

    /**
     * รับค่าจาก action (detail.php)
     *
     * @param Request $request
     */
    public function action(Request $request)
    {
        $ret = [];
        // session, referer, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::notDemoMode($login)) {
                // รับค่าจากการ POST
                $action = $request->post('action')->toString();
                // id ที่ส่งมา
                if (preg_match_all('/,?([0-9]+),?/', $request->post('id')->toString(), $match)) {
                    if ($action === 'delete' && Login::checkPermission($login, 'can_received_repair')) {
                        // ลบรายละเอียดซ่อม
                        $this->db()->delete($this->getTableName('repair_status'), array('id', (int) $match[1][0]));
                        // log
                        \Index\Log\Model::add(0, 'repair', 'Delete', '{LNG_Delete} {LNG_Transaction history} ID : '.$match[1][0], $login['id']);
                        // reload
                        $ret['location'] = 'reload';
                    }
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่า JSON
        echo json_encode($ret);
    }
}
