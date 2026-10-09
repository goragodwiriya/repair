<?php
/**
 * @filesource modules/repair/controllers/init.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Init;

use Gcms\Api as ApiController;

/**
 * ลงทะเบียนเมนูและสิทธิ์ของโมดูลรับซ่อม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Controller
{
    /**
     * สิทธิ์ของโมดูล (ใช้ชื่อเดียวกับระบบเดิม)
     *
     * @param array $permissions
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initPermission($permissions, $params = null, $login = null)
    {
        $permissions[] = [
            'value' => 'can_received_repair',
            'text' => '{LNG_Can get the repair}'
        ];
        $permissions[] = [
            'value' => 'can_repair',
            'text' => '{LNG_Repairman}'
        ];

        return $permissions;
    }

    /**
     * เมนูของโมดูล
     *
     * @param array $menus
     * @param mixed $params
     * @param object|null $login
     *
     * @return array
     */
    public static function initMenus($menus, $params = null, $login = null)
    {
        if (!$login) {
            return $menus;
        }

        // เจ้าหน้าที่รับซ่อม และ ช่างซ่อม
        if (ApiController::hasPermission($login, ['can_received_repair', 'can_repair'])) {
            $children = [
                [
                    'title' => '{LNG_Repair list}',
                    'url' => '/repair-jobs',
                    'icon' => 'icon-list'
                ]
            ];
            if (ApiController::hasPermission($login, 'can_received_repair')) {
                $children[] = [
                    'title' => '{LNG_Get a repair}',
                    'url' => '/repair-receive?id=0',
                    'icon' => 'icon-write'
                ];
            }

            $menus = parent::insertMenuAfter($menus, [
                [
                    'title' => '{LNG_Repair jobs}',
                    'icon' => 'icon-tools',
                    'children' => $children
                ]
            ], 'dashboard');
        }

        // เมนูตั้งค่า (มีเฉพาะผู้ที่ตั้งค่าระบบได้)
        if (ApiController::hasPermission($login, 'can_config')) {
            $menus = parent::insertMenuChildren($menus, [
                [
                    'title' => '{LNG_Repair Settings}',
                    'url' => '/repair-settings',
                    'icon' => 'icon-tools'
                ],
                [
                    'title' => '{LNG_Repair status}',
                    'url' => '/repair-statuses',
                    'icon' => 'icon-star0'
                ]
            ], 'settings');
        }

        return $menus;
    }
}
