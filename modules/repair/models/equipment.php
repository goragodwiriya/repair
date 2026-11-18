<?php
/**
 * @filesource modules/repair/models/equipment.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Equipment;

/**
 * ทะเบียนเครื่อง/อุปกรณ์ที่เคยรับซ่อม
 * ระบบเดิมเก็บไว้ในตาราง inventory ปัจจุบันคือ repair_equipment
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ค้นหาประวัติเครื่องที่เคยรับซ่อม สำหรับ autocomplete
     * ค้นจาก $field ก่อน ถ้าคำค้นว่างคืนค่า array ว่าง
     *
     * @param string $search คำค้น
     * @param string $field  equipment หรือ serial (คอลัมน์ที่ใช้ค้นและใช้เป็น value)
     * @param int    $limit
     *
     * @return array [{value, text, equipment, serial}]
     */
    public static function find($search, $field, $limit = 10)
    {
        if ($search === '') {
            return [];
        }

        $field = $field === 'serial' ? 'serial' : 'equipment';

        $result = static::createQuery()
            ->select('id', 'equipment', 'serial')
            ->from('repair_equipment')
            ->where([[$field, 'LIKE', '%'.$search.'%']])
            ->orderBy($field)
            ->limit((int) $limit)
            ->fetchAll(true);

        $datas = [];
        foreach ($result as $item) {
            $datas[] = [
                'value' => $item[$field],
                'text' => $item['serial'] === '' ? $item['equipment'] : $item['equipment'].' : '.$item['serial'],
                'equipment' => $item['equipment'],
                'serial' => $item['serial']
            ];
        }

        return $datas;
    }

    /**
     * ค้นหาเครื่องจากชื่อและหมายเลขเครื่อง ถ้ายังไม่มีจะเพิ่มรายการใหม่ให้
     * คืนค่า ID ของ repair_equipment
     *
     * @param string $equipment
     * @param string $serial
     *
     * @return int
     */
    public static function resolve($equipment, $serial)
    {
        $db = \Kotchasan\DB::create();

        $search = $db->first('repair_equipment', [
            ['equipment', $equipment],
            ['serial', $serial]
        ]);
        if ($search) {
            return (int) $search->id;
        }

        return (int) $db->insert('repair_equipment', [
            'equipment' => $equipment,
            'serial' => $serial,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
