<?php
/**
 * @filesource modules/repair/models/operator.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Operator;

/**
 * รายชื่อช่างซ่อม (สมาชิกที่มีสิทธิ์ can_repair)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * อ่านรายชื่อช่างซ่อมทั้งหมด
     *
     * @return array
     */
    public static function all()
    {
        return static::createQuery()
            ->select('id', 'name')
            ->from('user')
            ->where([
                ['active', 1],
                ['permission', 'LIKE', '%,can_repair,%']
            ])
            ->orderBy('id')
            ->fetchAll(true);
    }

    /**
     * รายชื่อช่างซ่อมสำหรับใส่ลงใน select
     *
     * @return array
     */
    public static function toOptions()
    {
        $result = [];
        foreach (self::all() as $item) {
            $result[] = [
                'value' => (int) $item['id'],
                'text' => $item['name']
            ];
        }

        return $result;
    }

    /**
     * รายชื่อช่างซ่อมในรูปแบบ [id => name]
     *
     * @return array
     */
    public static function map()
    {
        $result = [];
        foreach (self::all() as $item) {
            $result[(int) $item['id']] = $item['name'];
        }

        return $result;
    }
}
