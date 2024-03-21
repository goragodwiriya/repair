<?php
/**
 * @filesource modules/repair/controllers/initmenu.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Initmenu;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * Init Menu
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Kotchasan\KBase
{
    /**
     * ฟังก์ชั่นเริ่มต้นการทำงานของโมดูลที่ติดตั้ง
     * และจัดการเมนูของโมดูล.
     *
     * @param Request                $request
     * @param \Index\Menu\Controller $menu
     * @param array                  $login
     */
    public static function execute(Request $request, $menu, $login)
    {
        // repair module
        $menu->addTopLvlMenu('repair', '{LNG_Repair jobs}', 'index.php?module=repair-setup', null, 'member');
        if (Login::checkPermission($login, 'can_config')) {
            // ตั้งค่าโมดูล
            $menu->add('settings', '{LNG_Repair settings}', 'index.php?module=repair-settings', null, 'repair');
            foreach (Language::get('REPAIR_CATEGORIES') as $key => $value) {
                $menu->add('settings', $value, 'index.php?module=repair-category&amp;typ='.$key, null, 'category'.$key);
            }
        }
    }
}
