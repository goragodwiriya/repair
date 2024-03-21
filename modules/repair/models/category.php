<?php
/**
 * @filesource modules/repair/modules/category.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Category;

use Gcms\Login;
use Kotchasan\Database\Sql;
use Kotchasan\Language;

/**
 * module=memberstatus
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * ลิสต์รายการหมวดหมู่ ตาม $type
     *
     * @param int $type
     *
     * @return array
     */
    public static function all($type)
    {
        return \Kotchasan\Model::createQuery()
            ->select()
            ->from('category')
            ->where(array('type', $type))
            ->order('category_id')
            ->toArray()
            ->execute();
    }

    /**
     * อ่านรายการหมวดหมู่สำหรับใส่ลงใน select
     *
     * @param int $type
     *
     * @return array
     */
    public static function toSelect($type)
    {
        $result = [];
        foreach (self::all($type) as $item) {
            $result['category_id'] = $item['topic'];
        }
        return $result;
    }

    /**
     * รับค่าจาก action (category.php)
     */
    public function action()
    {
        $ret = [];
        // session, referer, can_config, ไม่ใช่สมาชิกตัวอย่าง
        if (self::$request->initSession() && self::$request->isReferer() && $login = Login::isMember()) {
            if (Login::checkPermission($login, 'can_config') && Login::notDemoMode($login)) {
                // ค่าที่ส่งมา
                $action = self::$request->post('action')->toString();
                $value = self::$request->post('value')->topic();
                // ตรวจสอบค่าที่ส่งมา
                if (preg_match('/^list_(add|delete|color|name|published|status)_([0-9]+)_([a-z]+)$/', $action, $match)) {
                    // Model
                    $model = new \Kotchasan\Model();
                    // ตารางหมวดหมู่
                    $table = $model->getTableName('category');
                    if ($match[1] == 'add') {
                        // เพิ่มแถวใหม่
                        $search = $model->createQuery()
                            ->from('category')
                            ->where(array('type', $match[3]))
                            ->first(Sql::create('MAX(CAST(`category_id` AS INT)) AS `category_id`'));
                        $category_id = empty($search->category_id) ? 1 : (1 + (int) $search->category_id);
                        $data = array(
                            'category_id' => $category_id,
                            'topic' => Language::get('Click to edit'),
                            'color' => '#000000',
                            'published' => 1,
                            'type' => $match[3]
                        );
                        $model->db()->insert($table, $data);
                        // คืนค่าแถวใหม่
                        $ret['data'] = Language::trans(\Repair\Category\View::createRow($data));
                        $ret['newId'] = 'list_'.$data['category_id'].'_'.$match[3];
                        // log
                        \Index\Log\Model::add(0, 'repair', 'Save', Language::get('REPAIR_CATEGORIES', '', $match[3]), $login['id']);
                    } elseif ($match[1] == 'delete') {
                        // ลบ
                        $model->db()->delete($table, array(
                            array('type', 'repairstatus'),
                            array('category_id', (int) $match[2])
                        ));
                        // คืนค่าแถวที่ลบ
                        $ret['del'] = 'list_'.$match[2].'_'.$match[3];
                    } elseif ($match[1] == 'color') {
                        // แก้ไขสี
                        $save = array('color' => $value);
                    } elseif ($match[1] == 'name') {
                        // แก้ไขชื่อ
                        $save = array('topic' => $value);
                    } elseif ($match[1] == 'published') {
                        // แก้ไขการเผยแพร่
                        $value = $value == 1 ? 0 : 1;
                        $save = array('published' => $value);
                    }
                    if (isset($save)) {
                        // บันทึก
                        $model->db()->update($table, array(
                            array('type', 'repairstatus'),
                            array('category_id', (int) $match[2])
                        ), $save);
                        // คืนค่าข้อมูลที่แก้ไข
                        $ret['edit'] = $value;
                        $ret['editId'] = $action;
                        // log
                        \Index\Log\Model::add(0, 'repair', ucfirst($match[1]), Language::get('REPAIR_CATEGORIES', '', $match[3]), $login['id']);
                    }
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
