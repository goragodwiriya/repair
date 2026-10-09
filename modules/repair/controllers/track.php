<?php
/**
 * @filesource modules/repair/controllers/track.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Track;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API หน้าติดตามสถานะการซ่อมสำหรับลูกค้า
 * เปิดให้เรียกได้โดยไม่ต้องเข้าระบบ เข้าถึงได้เมื่อรู้เลขที่ใบรับซ่อมเท่านั้น
 * (เหมือนระบบเดิมที่เปิดผ่าน QR Code บนใบรับซ่อม)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/repair/track/get?id=<job_id>
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');
            $this->initLanguage($request);

            // เลขที่ใบรับซ่อมมีรูปแบบ 4-20 ตัวอักษร ตัวเลข - และ _
            $job_id = $request->get('id')->filter('A-Za-z0-9\-_');
            if (strlen($job_id) < 4 || strlen($job_id) > 20) {
                return $this->errorResponse('Not Found', 404);
            }

            $index = \Repair\Detail\Model::getByJobId($job_id);
            if ($index === null) {
                return $this->errorResponse('Not Found', 404);
            }

            $company = isset(self::$cfg->company) && is_array(self::$cfg->company) ? self::$cfg->company : [];
            $statuses = \Repair\Status\Model::map(false);
            $logo = '';
            if (is_file(ROOT_PATH.DATA_FOLDER.'images/company_logo'.self::$cfg->stored_img_type)) {
                $logo = WEB_URL.DATA_FOLDER.'images/company_logo'.self::$cfg->stored_img_type;
            }

            return $this->successResponse([
                'data' => (object) [
                    'job_id' => (string) $index->job_id,
                    'name' => (string) $index->name,
                    'phone' => (string) $index->phone,
                    'address' => trim($index->address.' '.\Kotchasan\Province::get($index->provinceID).' '.$index->zipcode),
                    'equipment' => (string) $index->equipment,
                    'serial' => (string) $index->serial,
                    'job_description' => \Kotchasan\Text::untextarea((string) $index->job_description),
                    'created_at' => $index->created_at,
                    'appointment_date' => (string) $index->appointment_date,
                    'appraiser' => (float) $index->appraiser,
                    'appraiser_text' => empty($index->appraiser)
                        ? '-'
                        : \Kotchasan\Currency::format($index->appraiser).' '.Language::get('CURRENCY_UNITS', '', self::$cfg->currency_unit),
                    'status' => (int) $index->status,
                    'status_text' => \Repair\Status\Model::topicOf($statuses, $index->status),
                    'status_style' => 'background-color:'.\Repair\Status\Model::colorOf($statuses, $index->status),
                    'company_name' => isset($company['name']) && $company['name'] !== '' ? $company['name'] : self::$cfg->web_title,
                    'company_phone' => isset($company['phone']) ? $company['phone'] : '',
                    'company_logo' => $logo,
                    'timeline' => [
                        'data' => \Repair\Detail\Model::timeline($index->id)
                    ]
                ]
            ], 'Repair status retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
