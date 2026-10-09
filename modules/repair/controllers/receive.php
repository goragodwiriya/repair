<?php
/**
 * @filesource modules/repair/controllers/receive.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Receive;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API ฟอร์มรับซ่อม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/repair/receive/get
     * อ่านข้อมูลใบรับซ่อมสำหรับฟอร์ม
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function get(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'GET');

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->errorResponse('Unauthorized', 401);
            }
            if (!ApiController::hasPermission($login, 'can_received_repair')) {
                return $this->errorResponse('Permission required', 403);
            }

            // จังหวัดและรหัสไปรษณีย์ที่ใช้ล่าสุด (จำไว้ใน cookie เหมือนระบบเดิม)
            $defaults = [
                'provinceID' => $request->hasCookie('repair_provinceID') ? (int) $request->cookie('repair_provinceID', 0) : 10,
                'zipcode' => $request->hasCookie('repair_zipcode') ? (string) $request->cookie('repair_zipcode', '') : '10000'
            ];
            if (empty($defaults['provinceID'])) {
                $defaults['provinceID'] = 10;
            }
            if ($defaults['zipcode'] === '') {
                $defaults['zipcode'] = '10000';
            }

            $index = Model::get($request->get('id', 0)->toInt(), $defaults);
            if ($index === null) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            return $this->successResponse([
                'data' => $index,
                'options' => [
                    'provinceID' => \Kotchasan\Province::getOptions()
                ]
            ], 'Repair details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST api/repair/receive/save
     * บันทึกใบรับซ่อม
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function save(Request $request)
    {
        try {
            ApiController::validateMethod($request, 'POST');
            $this->validateCsrfToken($request);

            $login = $this->authenticateRequest($request);
            if (!$login) {
                return $this->redirectResponse('/login', 'Unauthorized', 401);
            }
            if (!ApiController::canModify($login, ['can_received_repair'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $id = $request->post('id', 0)->toInt();
            $name = self::inputText($request, 'name');
            $equipment = self::inputText($request, 'equipment');
            $serial = self::inputText($request, 'serial');

            $errors = [];
            if ($name === '') {
                $errors['name'] = 'Please fill in';
            }
            if ($equipment === '') {
                $errors['equipment'] = 'Please fill in';
            }
            if (!empty($errors)) {
                return $this->formErrorResponse($errors, 400);
            }

            $repair = [
                'name' => $name,
                'phone' => self::inputText($request, 'phone'),
                'address' => $request->post('address')->topic(),
                'provinceID' => $request->post('provinceID')->toInt(),
                'zipcode' => $request->post('zipcode')->number(),
                'job_description' => $request->post('job_description')->textarea(),
                'appointment_date' => $request->post('appointment_date')->date(),
                'appraiser' => $request->post('appraiser')->toDouble(),
                'equipment_id' => \Repair\Equipment\Model::resolve($equipment, $serial)
            ];
            $comment = $request->post('comment')->topic();

            if ($id === 0) {
                // รายการใหม่ ใช้เวลาปัจจุบันเป็นวันที่รับซ่อมเสมอ (เหมือนระบบเดิม)
                $repair['created_at'] = date('Y-m-d H:i:s');

                list($id, $job_id) = Model::createJob($repair, $comment, (int) $login->id);

                $message = Language::get('Saved successfully');
                // แจ้งเตือนช่างซ่อมผ่าน Telegram เหมือนระบบเดิม
                if (!empty(self::$cfg->telegram_chat_id)) {
                    $error = \Gcms\Telegram::sendTo(
                        self::$cfg->telegram_chat_id,
                        Language::get('Get a repair').' '.$job_id.' '.WEB_URL.'repair-detail?id='.$id
                    );
                    if (!empty($error)) {
                        $message = $error;
                    }
                }

                \Index\Log\Model::add($id, 'repair', 'Save', 'Get a repair ID : '.$id, $login->id);
            } else {
                $index = Model::get($id);
                if ($index === null) {
                    return $this->errorResponse('Sorry, Item not found It&#39;s may be deleted', 404);
                }

                // แก้ไขวันที่รับซ่อมได้ แต่คงเวลาเดิมไว้
                $created = $request->post('created_at')->date();
                if (!empty($created) && $created !== $index->created_at) {
                    $raw = \Repair\Detail\Model::get($id);
                    $repair['created_at'] = $created.' '.date('H:i:s', strtotime($raw->created_at));
                }

                Model::updateJob($index, $repair, $comment, (int) $login->id);
                $job_id = $index->job_id;
                $message = Language::get('Saved successfully');

                \Index\Log\Model::add($id, 'repair', 'Save', 'Repair job description ID : '.$id, $login->id);
            }

            // จำจังหวัดและรหัสไปรษณีย์ที่ใช้ล่าสุด
            self::rememberAddress($repair['provinceID'], $repair['zipcode']);

            $actions = [
                [
                    'type' => 'notification',
                    'level' => 'success',
                    'message' => $message
                ]
            ];
            if ($request->post('print')->toInt() === 1) {
                // เปิดใบรับซ่อมในแท็บใหม่
                $actions[] = [
                    'type' => 'download',
                    'url' => WEB_URL.'export.php?module=repair&typ=receipt&id='.rawurlencode($job_id)
                ];
            }
            $actions[] = [
                'type' => 'redirect',
                'url' => '/repair-jobs',
                'delay' => 1000
            ];

            return $this->successResponse([
                'actions' => $actions
            ], $message);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * อ่านค่าจากช่องที่เปิด autocomplete ไว้
     * Now.js เปลี่ยนชื่อ input ที่มองเห็นเป็น <name>_text และเพิ่ม hidden <name> ให้
     * ค่าที่ผู้ใช้พิมพ์เองจะอยู่ใน <name>_text เท่านั้น
     *
     * @param Request $request
     * @param string $name
     *
     * @return string
     */
    protected static function inputText(Request $request, $name)
    {
        $value = $request->post($name.'_text')->topic();
        if ($value === '') {
            $value = $request->post($name)->topic();
        }

        return $value;
    }

    /**
     * จำจังหวัดและรหัสไปรษณีย์ที่กรอกล่าสุดไว้ใน cookie
     *
     * @param int $provinceID
     * @param string $zipcode
     *
     * @return void
     */
    protected static function rememberAddress($provinceID, $zipcode)
    {
        if (headers_sent()) {
            return;
        }

        $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
        $options = [
            'expires' => time() + 2592000,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $secure ? 'Strict' : 'Lax'
        ];
        setcookie('repair_provinceID', (string) $provinceID, $options);
        setcookie('repair_zipcode', (string) $zipcode, $options);
    }
}
