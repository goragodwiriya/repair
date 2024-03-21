<?php
/**
 * @filesource modules/repair/models/autocomplete.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Autocomplete;

use Gcms\Login;
use Kotchasan\Http\Request;

/**
 * ค้นหา สำหรับ autocomplete
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ค้นหาประวัติ
     * คืนค่าเป็น JSON
     *
     * @param Request $request
     */
    public function findCustomer(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            try {
                $search = $request->post('name')->topic();
                if ($search != '') {
                    $where = array('name', 'LIKE', "%$search%");
                    $order = 'name';
                } else {
                    $search = $request->post('phone')->topic();
                    if ($search != '') {
                        $where = array('phone', 'LIKE', "%$search%");
                        $order = 'phone';
                    }
                }
                if (isset($where)) {
                    $result = $this->db()->createQuery()
                        ->select('name', 'phone', 'address', 'provinceID', 'zipcode')
                        ->from('repair')
                        ->where($where)
                        ->order($order)
                        ->limit($request->post('count')->toInt())
                        ->toArray()
                        ->execute();
                    if (!empty($result)) {
                        // คืนค่า JSON
                        echo json_encode($result);
                    }
                }
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }

    /**
     * ค้นหาประวัติการซ่อมสินค้า สำหรับ autocomplete
     * คืนค่าเป็น JSON
     *
     * @param Request $request
     */
    public function findInventory(Request $request)
    {
        if ($request->initSession() && $request->isReferer() && Login::isMember()) {
            try {
                $search = $request->post('equipment')->topic();
                if ($search != '') {
                    $where = array('equipment', 'LIKE', "%$search%");
                    $order = 'equipment';
                } else {
                    $search = $request->post('serial')->topic();
                    if ($search != '') {
                        $where = array('serial', 'LIKE', "%$search%");
                        $order = 'serial';
                    }
                }
                if (isset($where)) {
                    $result = $this->db()->createQuery()
                        ->select('id inventory_id', 'equipment', 'serial')
                        ->from('inventory')
                        ->where($where)
                        ->order($order)
                        ->limit($request->post('count')->toInt())
                        ->toArray()
                        ->execute();
                    if (!empty($result)) {
                        // คืนค่า JSON
                        echo json_encode($result);
                    }
                }
            } catch (\Kotchasan\InputItemException $e) {
            }
        }
    }
}
