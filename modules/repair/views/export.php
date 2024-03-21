<?php
/**
 * @filesource modules/repair/views/export.php
 *
 * @copyright 2016 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Export;

use Kotchasan\Currency;
use Kotchasan\Date;
use Kotchasan\Language;
use Kotchasan\Province;
use Kotchasan\Template;

/**
 * export.php
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class View extends \Gcms\View
{
    /**
     * พิมพ์รายละเอียดการซ่อม
     *
     * @return string
     */
    public function render($index)
    {
        // URL สำหรับดูรายละเอียดการซ่อม
        $url = WEB_URL.'repair.php?id='.$index->job_id;
        // QR Code
        $qrcode_file = DATA_FOLDER.md5($url.'|'.'H'.'|'.'2').'.png';
        $filename = ROOT_PATH.$qrcode_file;
        if (!file_exists($filename)) {
            // include qrlib
            require '../../phpqrcode/qrlib.php';
            // create QR Code
            \QRcode::png($url, $filename, 'H', 2, 2);
        }
        // template
        $template = Template::createFromFile(ROOT_PATH.'modules/repair/views/print.html');
        $template->add(array(
            '/%COMPANY%/' => isset(self::$cfg->company_name) ? self::$cfg->company_name : '',
            '/%COMPANYADDRESS%/' => isset(self::$cfg->address) ? self::$cfg->address : '',
            '/%COMPANYPHONE%/' => isset(self::$cfg->phone) ? self::$cfg->phone : '',
            '/%JOB_ID%/' => $index->job_id,
            '/%NAME%/' => $index->name,
            '/%PHONE%/' => $index->phone,
            '/%ADDRESS%/' => $index->address,
            '/%PROVINCE%/' => Province::get($index->provinceID),
            '/%ZIPCODE%/' => $index->zipcode,
            '/%EQUIPMENT%/' => $index->equipment,
            '/%SERIAL%/' => $index->serial,
            '/%JOB_DESCRIPTION%/' => nl2br($index->job_description),
            '/%CREATE_DATE%/' => Date::format($index->create_date, 'd M Y'),
            '/%APPOINTMENT_DATE%/' => Date::format($index->appointment_date, 'd M Y'),
            '/%APPRAISER%/' => $index->appraiser == 0 ? str_repeat('&nbsp;', 20) : Currency::format($index->appraiser),
            '/%COMMENT%/' => $index->comment,
            '/%URL%/' => $url,
            '/%QRCODE%/' => WEB_URL.$qrcode_file,
            '/{UNIT}/' => isset(self::$cfg->currency_unit) ? Language::get('CURRENCY_UNITS', null, self::$cfg->currency_unit) : '',
            '/{WEBURL}/' => WEB_URL
        ));
        // คืนค่า HTML
        return Language::trans($template->render());
    }
}
