<?php
/**
 * @filesource modules/repair/controllers/action.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Action;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API ปรับปรุงสถานะการซ่อม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/repair/action/get
     * ข้อมูลสำหรับฟอร์มปรับปรุงสถานะการซ่อม
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

            $payload = Model::modalPayload($request->get('id', 0)->toInt(), $login);
            if ($payload === null) {
                return $this->errorResponse('No data available', 404);
            }

            return $this->successResponse($payload, 'Repair status form');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST api/repair/action/save
     * บันทึกการปรับปรุงสถานะการซ่อม
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
            if (!ApiController::canModify($login, ['can_received_repair', 'can_repair'])) {
                return $this->errorResponse('Permission required', 403);
            }

            $repair_id = $request->post('repair_id')->toInt();
            $index = \Repair\Detail\Model::get($repair_id);
            if ($index === null) {
                return $this->errorResponse('No data available', 404);
            }

            $status = $request->post('status')->toInt();
            $statuses = \Repair\Status\Model::map(false);
            if (!isset($statuses[$status])) {
                return $this->formErrorResponse(['status' => 'Please select'], 400);
            }

            // ช่างซ่อมที่ไม่ใช่เจ้าหน้าที่รับซ่อม รับผิดชอบงานด้วยตัวเอง
            $operator_id = ApiController::hasPermission($login, 'can_received_repair')
                ? $request->post('operator_id', (int) $login->id)->toInt()
                : (int) $login->id;

            \Repair\Detail\Model::addStatus([
                'repair_id' => $repair_id,
                'member_id' => (int) $login->id,
                'operator_id' => $operator_id,
                'status' => $status,
                'comment' => $request->post('comment')->textarea(),
                'cost' => $request->post('cost')->toDouble(),
                'created_at' => date('Y-m-d H:i:s')
            ]);

            \Index\Log\Model::add($repair_id, 'repair', 'Save', 'Update repair status ID : '.$repair_id, $login->id);

            return $this->successResponse([
                'actions' => [
                    [
                        'type' => 'notification',
                        'level' => 'success',
                        'message' => \Kotchasan\Language::get('Saved successfully')
                    ],
                    [
                        'type' => 'modal',
                        'action' => 'close'
                    ],
                    [
                        'type' => 'redirect',
                        'url' => 'reload',
                        'target' => 'table',
                        'delay' => 500
                    ]
                ]
            ], 'Saved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
