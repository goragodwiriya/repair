<?php
/**
 * @filesource modules/repair/controllers/dashboard.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Dashboard;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API ข้อมูลสรุปงานซ่อมสำหรับหน้าแรก
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/repair/dashboard/get
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
            if (!ApiController::hasPermission($login, ['can_received_repair', 'can_repair'])) {
                return $this->errorResponse('Permission required', 403);
            }

            return $this->successResponse([
                'data' => Model::summary($login)
            ], 'Repair summary loaded');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
