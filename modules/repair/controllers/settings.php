<?php
/**
 * @filesource modules/repair/controllers/settings.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Settings;

use Gcms\Api as ApiController;
use Gcms\Config;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * API ตั้งค่าโมดูลรับซ่อม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/repair/settings/get
     * อ่านค่ากำหนดของโมดูล
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
            if (!ApiController::hasPermission($login, 'can_config')) {
                return $this->errorResponse('Permission required', 403);
            }

            return $this->successResponse([
                'data' => (object) [
                    'repair_first_status' => (int) self::$cfg->repair_first_status,
                    'repair_prefix' => (string) self::$cfg->repair_prefix,
                    'repair_job_no' => (string) self::$cfg->repair_job_no,
                    'currency_unit' => (string) self::$cfg->currency_unit
                ],
                'options' => [
                    'repair_first_status' => \Repair\Status\Model::toOptions(false),
                    'currency_unit' => self::currencyOptions()
                ]
            ], 'Repair settings loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST api/repair/settings/save
     * บันทึกค่ากำหนดของโมดูล
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
            if (!ApiController::canModify($login, ['can_config'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $config = Config::load(ROOT_PATH.'settings/config.php');
            $config->repair_first_status = $request->post('repair_first_status')->toInt();
            $config->repair_prefix = $request->post('repair_prefix')->topic();
            $config->repair_job_no = $request->post('repair_job_no')->topic();
            $config->currency_unit = $request->post('currency_unit')->topic();

            if (!Config::save($config, ROOT_PATH.'settings/config.php')) {
                return $this->errorResponse(Language::replace('File %s cannot be created or is read-only.', 'settings/config.php'), 500);
            }

            \Index\Log\Model::add(0, 'repair', 'Save', 'Repair Settings', $login->id);

            return $this->redirectResponse('reload', 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * รายการหน่วยสกุลเงินสำหรับใส่ลงใน select
     *
     * @return array
     */
    protected static function currencyOptions()
    {
        $result = [];
        $units = Language::get('CURRENCY_UNITS');
        if (is_array($units)) {
            foreach ($units as $key => $value) {
                $result[] = [
                    'value' => $key,
                    'text' => $value
                ];
            }
        }

        return $result;
    }
}
