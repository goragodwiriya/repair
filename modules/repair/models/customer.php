<?php
/**
 * @filesource modules/repair/models/customer.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Customer;

/**
 * ประวัติลูกค้า อ่านจากใบรับซ่อมที่เคยบันทึกไว้
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * ค้นหาประวัติลูกค้าสำหรับ autocomplete
     * ค้นจาก $field (name หรือ phone) และคืนค่าที่อยู่มาให้เติมฟอร์มด้วย
     *
     * @param string $search คำค้น
     * @param string $field  name หรือ phone (คอลัมน์ที่ใช้ค้นและใช้เป็น value)
     * @param int    $limit
     *
     * @return array [{value, text, name, phone, address, provinceID, zipcode}]
     */
    public static function find($search, $field, $limit = 10)
    {
        if ($search === '') {
            return [];
        }

        $field = $field === 'phone' ? 'phone' : 'name';

        $result = static::createQuery()
            ->select('name', 'phone', 'address', 'provinceID', 'zipcode')
            ->from('repair')
            ->where([[$field, 'LIKE', '%'.$search.'%']])
            ->groupBy('name', 'phone', 'address', 'provinceID', 'zipcode')
            ->orderBy($field)
            ->limit((int) $limit)
            ->fetchAll(true);

        $datas = [];
        foreach ($result as $item) {
            $datas[] = [
                'value' => (string) $item[$field],
                'text' => trim($item['name'].' '.$item['phone']),
                'name' => (string) $item['name'],
                'phone' => (string) $item['phone'],
                'address' => (string) $item['address'],
                'provinceID' => (string) $item['provinceID'],
                'zipcode' => (string) $item['zipcode']
            ];
        }

        return $datas;
    }
}
