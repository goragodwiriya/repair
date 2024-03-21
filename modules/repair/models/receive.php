<?php
/**
 * @filesource modules/repair/models/receive.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Receive;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Http\Request;
use Kotchasan\Language;
use Kotchasan\Number;

/**
 * module=repair-receive
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านข้อมูลรายการที่เลือก
     * ถ้า $id = 0 หมายถึงรายการใหม่
     * คืนค่าข้อมูล object ไม่พบคืนค่า null
     *
     * @param int $id ID
     *
     * @return object|null
     */
    public static function get($id)
    {
        if (empty($id)) {
            // ใหม่
            return (object) array(
                'name' => '',
                'phone' => '',
                'address' => '',
                'provinceID' => self::$request->cookie('repair_provinceID', 102)->number(),
                'zipcode' => self::$request->cookie('repair_zipcode', 10000)->number(),
                'user_status' => 0,
                'equipment' => '',
                'serial' => '',
                'inventory_id' => 0,
                'job_description' => '',
                'create_date' => date('Y-m-d H:i:s'),
                'appointment_date' => date('Y-m-d'),
                'appraiser' => '',
                'id' => 0,
                'comment' => '',
                'status_id' => 0,
                'job_id' => ''
            );
        } else {
            // แก้ไข
            $model = new static;
            $q1 = $model->db()->createQuery()
                ->select('repair_id', Sql::MAX('id', 'max_id'))
                ->from('repair_status')
                ->groupBy('repair_id');
            return $model->db()->createQuery()
                ->from('repair R')
                ->join(array($q1, 'T'), 'LEFT', array('T.repair_id', 'R.id'))
                ->join('repair_status S', 'LEFT', array('S.id', 'T.max_id'))
                ->join('inventory V', 'LEFT', array('V.id', 'R.inventory_id'))
                ->where(array('R.id', $id))
                ->first('R.*', 'V.equipment', 'V.serial', 'S.status', 'S.comment', 'S.cost', 'S.operator_id', 'S.id status_id');
        }
    }

    /**
     * บันทึกค่าจากฟอร์ม (receive.php)
     *
     * @param Request $request
     */
    public function submit(Request $request)
    {
        $ret = [];
        // session, token, can_received_repair, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_received_repair') && Login::notDemoMode($login)) {
                try {
                    // รับค่าจากการ POST
                    $inventory = array(
                        'equipment' => $request->post('equipment')->topic(),
                        'serial' => $request->post('serial')->topic()
                    );
                    $repair = array(
                        'job_description' => $request->post('job_description')->textarea(),
                        'create_date' => $request->post('create_date')->date(),
                        'appointment_date' => $request->post('appointment_date')->date(),
                        'appraiser' => $request->post('appraiser')->toDouble(),
                        'inventory_id' => $request->post('inventory_id')->toInt(),
                        'name' => $request->post('name')->topic(),
                        'phone' => $request->post('phone')->topic(),
                        'address' => $request->post('address')->topic(),
                        'provinceID' => $request->post('provinceID')->number(),
                        'zipcode' => $request->post('zipcode')->number()
                    );
                    $log = array(
                        'member_id' => $login['id'],
                        'comment' => $request->post('comment')->topic(),
                        'operator_id' => 0
                    );
                    // ตรวจสอบรายการที่เลือก
                    $index = self::get($request->post('id')->toInt());
                    if ($index) {
                        // ตาราง
                        $inventory_table = $this->getTableName('inventory');
                        $repair_table = $this->getTableName('repair');
                        $repair_status_table = $this->getTableName('repair_status');
                        // Database
                        $db = $this->db();
                        // name
                        if (empty($repair['name'])) {
                            $ret['ret_name'] = 'Please fill in';
                        }
                        // equipment
                        if (empty($inventory['equipment'])) {
                            $ret['ret_equipment'] = 'this';
                        }
                        if (empty($ret)) {
                            // ตรวจสอบรายการพัสดุเดิม
                            $search = $db->first($inventory_table, array(
                                array('equipment', $inventory['equipment']),
                                array('serial', $inventory['serial'])
                            ));
                            if (!$search) {
                                // บันทึกพัสดุรายการใหม่
                                $inventory['create_date'] = time();
                                $repair['inventory_id'] = $db->insert($inventory_table, $inventory);
                            } else {
                                // มีพัสดุเดิมอยู่ก่อนแล้ว
                                $repair['inventory_id'] = $search->id;
                            }
                            $err = '';
                            // job_id
                            if ($index->id == 0) {
                                // ID ใหม่
                                $repair['id'] = $db->getNextId($repair_table);
                                if (!empty(self::$cfg->repair_job_no) && self::$cfg->repair_job_no !== '%00d') {
                                    // running number
                                    $repair['job_id'] = \Index\Number\Model::get(0, 'repair_job_no', $repair_table, 'job_id', self::$cfg->repair_prefix);
                                } else {
                                    // สุ่ม job_id
                                    $repair['job_id'] = strtoupper(substr(uniqid(), 0, 10));
                                    // ตรวจสอบ job_id ซ้ำ
                                    while ($db->first($repair_table, array('job_id', $repair['job_id']))) {
                                        $repair['job_id'] = strtoupper(substr(uniqid(), 0, 10));
                                    }
                                }
                                $repair['create_date'] = date('Y-m-d H:i:s');
                                $log['create_date'] = $repair['create_date'];
                                // บันทึกรายการแจ้งซ่อม
                                $db->insert($repair_table, $repair);
                                $log['repair_id'] = $repair['id'];
                                $log['status'] = isset(self::$cfg->repair_first_status) ? self::$cfg->repair_first_status : 1;
                                $title = '{LNG_Get a repair}';
                                // ส่งข้อความแจ้งเตือนทาง Line ไปยังช่างซ่อม
                                if (!empty(self::$cfg->line_api_key)) {
                                    $message = $title.' '.WEB_URL.'index.php?module=repair-detail&id='.$log['repair_id'];
                                    $err = \Gcms\Line::send($message, self::$cfg->line_api_key);
                                }
                            } else {
                                // แก้ไขรายการแจ้งซ่อม
                                $db->update($repair_table, $index->id, $repair);
                                $log['repair_id'] = $index->id;
                                $repair['job_id'] = $index->job_id;
                                $title = '{LNG_Repair job description}';
                            }
                            // บันทึกประวัติการทำรายการ
                            if (empty($index->status_id)) {
                                $repair['id'] = $db->insert($repair_status_table, $log);
                            } else {
                                $db->update($repair_status_table, $index->status_id, $log);
                            }
                            // log
                            \Index\Log\Model::add($log['repair_id'], 'repair', 'Save', $title, $login['id']);
                            // คืนค่า
                            $ret['alert'] = $err === '' ? Language::get('Saved successfully') : $err;
                            $ret['location'] = 'index.php?module=repair-setup';
                            if ($request->post('print')->toString() == 1) {
                                $ret['open'] = WEB_URL.'modules/repair/print.php?id='.$repair['job_id'];
                            }
                            // clear
                            $request->removeToken();
                            // save cookie
                            setcookie('repair_provinceID', $repair['provinceID'], time() + 2592000, '/', HOST, HTTPS, true);
                            setcookie('repair_zipcode', $repair['zipcode'], time() + 2592000, '/', HOST, HTTPS, true);
                        }
                    } else {
                        // ไม่พบรายการที่แก้ไข
                        $ret['alert'] = Language::get('Sorry, Item not found It&#39;s may be deleted');
                    }
                } catch (\Kotchasan\InputItemException $e) {
                    $ret['alert'] = $e->getMessage();
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }
}
