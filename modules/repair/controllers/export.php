<?php
/**
 * @filesource modules/repair/controllers/export.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Export;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * ใบรับซ่อมสำหรับสั่งพิมพ์
 * เรียกผ่าน export.php?module=repair&typ=receipt&id=<job_id>
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * ใบรับซ่อม
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function receipt(Request $request)
    {
        try {
            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!ApiController::hasPermission($login, ['can_received_repair', 'can_repair'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $job_id = $request->get('id')->filter('A-Za-z0-9\-_');
            $index = \Repair\Detail\Model::getByJobId($job_id);
            if ($index === null) {
                return $this->errorResponse('Not Found', 404);
            }

            // ⚠️ สัญญาของ Export\Export\Controller::printHtml เปลี่ยนเป็น
            //    printHtml($title, $content, $options) แล้ว (ของเดิมรับอาเรย์
            //    regex→ค่าแทนที่ กับ extra_head) · ตัวเลือกที่รองรับตอนนี้คือ
            //    stylesheets[] · body_class · page_style — ดูตัวอย่างที่
            //    modules/inventory/controllers/export.php
            return \Export\Export\Controller::printHtml(
                Language::get('Repair receipt').' '.$index->job_id,
                '<div class="print-bar noprint"><div><button type="button" class="print-button" onclick="window.print()">{LNG_Print}</button></div></div>'
                .'<div class="print-content">'.self::sheet($index).'</div>',
                [
                    'body_class' => 'repair-print paper-a4',
                    'page_style' => \Export\Export\Controller::pageStyle(
                        \Export\Export\Controller::paper('a4')
                    ),
                    'stylesheets' => ['modules/repair/views/receipt.css']
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * HTML ของใบรับซ่อม 1 แผ่น
     *
     * @param object $index
     *
     * @return string
     */
    protected static function sheet($index)
    {
        $company = isset(self::$cfg->company) && is_array(self::$cfg->company) ? self::$cfg->company : [];
        $url = WEB_URL.'repair-track?id='.rawurlencode($index->job_id);
        $qrcode = self::qrcode($url);
        $unit = Language::get('CURRENCY_UNITS', '', self::$cfg->currency_unit);
        $address = trim($index->address.' '.\Kotchasan\Province::get($index->provinceID).' '.$index->zipcode);

        $logo = '';
        if (is_file(ROOT_PATH.DATA_FOLDER.'images/company_logo'.self::$cfg->stored_img_type)) {
            $logo = '<img class="company-logo" src="'.WEB_URL.DATA_FOLDER.'images/company_logo'.self::$cfg->stored_img_type.'" alt="">';
        }

        $name = isset($company['name']) && $company['name'] !== '' ? $company['name'] : self::$cfg->web_title;
        $received = \Kotchasan\Date::format($index->created_at, 'd M Y H:i');

        $html = '<section class="sheet repair-receipt">';
        // หัวกระดาษ : ชื่อหน่วยงานซ้าย ชนิดเอกสารขวา
        $html .= '<header class="sheet-head"><div class="brand">';
        if ($logo !== '') {
            $html .= '<div class="brand-logo">'.$logo.'</div>';
        }
        $html .= '<div class="brand-text">';
        $html .= '<div class="eyebrow">'.self::e(Language::get('Repair system')).'</div>';
        $html .= '<h1>'.self::e($name).'</h1><div class="brand-meta">';
        if (!empty($company['address'])) {
            $html .= '<div>'.nl2br(self::e($company['address'])).'</div>';
        }
        if (!empty($company['phone'])) {
            $html .= '<div>'.self::e(Language::get('Phone')).' '.self::e($company['phone']).'</div>';
        }
        $html .= '</div></div></div>';
        $html .= '<div class="doc-card"><h2>'.self::e(Language::get('Repair receipt')).'</h2>';
        $html .= '<div class="badge-row"><span class="badge">'.self::e($index->job_id).'</span></div>';
        $html .= '<div class="doc-meta">';
        $html .= '<div><div class="doc-label">'.self::e(Language::get('Received date')).'</div><div class="doc-value doc-date">'.self::e($received).'</div></div>';
        $html .= '<div><div class="doc-label">'.self::e(Language::get('Appointment date')).'</div><div class="doc-value doc-date">'.self::e(empty($index->appointment_date) ? '-' : \Kotchasan\Date::format($index->appointment_date, 'd M Y')).'</div></div>';
        $html .= '</div></div></header>';
        // ลูกค้า และ เครื่องที่รับซ่อม
        $html .= '<section class="party">';
        $html .= '<div class="party-card"><h3>'.self::e(Language::get('Customer')).'</h3>';
        $html .= '<div class="party-name">'.self::e($index->name).'</div>';
        $html .= '<p>'.self::e(Language::get('Phone')).' '.self::e($index->phone).'</p>';
        $html .= '<p>'.self::e($address).'</p></div>';
        $html .= '<div class="party-card"><h3>'.self::e(Language::get('Equipment')).'</h3>';
        $html .= '<div class="party-name">'.self::e($index->equipment).'</div>';
        $html .= '<p>'.self::e(Language::get('Serial/Registration No.')).' '.self::e($index->serial).'</p></div>';
        $html .= '</section>';
        // รายละเอียดการซ่อม
        $html .= '<div class="items-wrap"><table class="items"><thead><tr>';
        $html .= '<th>'.self::e(Language::get('Problems and repairs details')).'</th>';
        $html .= '</tr></thead><tbody><tr><td class="col-topic receipt-detail">';
        $html .= '<div class="item-title">'.nl2br(self::e($index->job_description)).'</div>';
        if ($index->comment !== null && $index->comment !== '') {
            $html .= '<div class="item-note">'.self::e(Language::get('Repair note')).' : '.nl2br(self::e(\Kotchasan\Text::untextarea($index->comment))).'</div>';
        }
        $html .= '</td></tr><tr class="filler"><td></td></tr></tbody></table></div>';
        // สรุป : QR Code ติดตามสถานะ และ ราคาประเมิน
        $html .= '<section class="sheet-sum">';
        $html .= '<div class="note-card receipt-track"><div><h3>'.self::e(Language::get('Check the repair status at')).'</h3>';
        $html .= '<div class="comment">'.self::e($url).'</div></div>';
        $html .= '<img class="receipt-qr" src="'.$qrcode.'" alt="'.self::e($url).'"></div>';
        $html .= '<div class="sum-card"><h3>'.self::e(Language::get('Appraiser')).'</h3>';
        $html .= '<div class="sum-row grand"><span>'.self::e(Language::get('Appraiser')).'</span><b>';
        $html .= self::e(empty($index->appraiser) ? '-' : \Kotchasan\Currency::format($index->appraiser).' '.$unit);
        $html .= '</b></div></div>';
        $html .= '</section>';
        // ลายเซ็น
        $html .= '<section class="signs receipt-signs">';
        foreach (['Customer', 'Operator'] as $role) {
            $html .= '<div class="sign-box"><div class="sign-line"></div>';
            $html .= '<div class="sign-name">(&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)</div>';
            $html .= '<div class="sign-role">'.self::e(Language::get($role)).'</div>';
            $html .= '<div class="sign-date">'.self::e(Language::get('Date')).' ......../......../........</div></div>';
        }
        $html .= '</section>';
        // ท้ายกระดาษ
        $html .= '<footer class="sheet-foot"><span><b>'.self::e($index->job_id).'</b></span>';
        $html .= '<span>'.self::e(Language::get('Printed')).' '.self::e(\Kotchasan\Date::format(date('Y-m-d H:i:s'), 'd M Y H:i')).'</span></footer>';
        $html .= '</section>';

        return $html;
    }

    /**
     * สร้าง QR Code ของ URL ติดตามสถานะ คืนค่า URL ของรูป
     * เก็บไฟล์ไว้ใน datas/repair/ เพื่อไม่ต้องสร้างใหม่ทุกครั้ง
     *
     * @param string $url
     *
     * @return string
     */
    protected static function qrcode($url)
    {
        $dir = ROOT_PATH.DATA_FOLDER.'repair/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $name = md5($url.'|H|2').'.png';
        $filename = $dir.$name;
        if (!is_file($filename)) {
            include_once ROOT_PATH.'modules/repair/phpqrcode/qrlib.php';
            \QRcode::png($url, $filename, 'H', 2, 2);
        }

        return WEB_URL.DATA_FOLDER.'repair/'.$name;
    }

    /**
     * escape ข้อความสำหรับใส่ลงใน HTML
     *
     * @param string $text
     *
     * @return string
     */
    protected static function e($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
