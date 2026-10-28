<?php
/**
 * @filesource modules/repair/controllers/autocomplete.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Autocomplete;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API ค้นหาประวัติลูกค้าและเครื่องที่เคยรับซ่อม สำหรับฟอร์มรับซ่อม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/repair/autocomplete/customer?q=&field=name|phone
     * ค้นหาประวัติลูกค้า
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function customer(Request $request)
    {
        try {
            $login = $this->authorize($request);
            if ($login instanceof \Kotchasan\Http\Response) {
                return $login;
            }

            return $this->successResponse(
                \Repair\Customer\Model::find($this->keyword($request), $request->get('field')->filter('a-z'), $this->limit($request)),
                'Search completed'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * GET api/repair/autocomplete/equipment?q=&field=equipment|serial
     * ค้นหาเครื่องที่เคยรับซ่อม
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function equipment(Request $request)
    {
        try {
            $login = $this->authorize($request);
            if ($login instanceof \Kotchasan\Http\Response) {
                return $login;
            }

            return $this->successResponse(
                \Repair\Equipment\Model::find($this->keyword($request), $request->get('field')->filter('a-z'), $this->limit($request)),
                'Search completed'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * ตรวจสอบ method และสิทธิ์ คืนค่า login หรือ error response
     * ผู้เรียกต้องเช็คด้วย instanceof Response เพราะทั้งสองแบบเป็น object
     *
     * @param Request $request
     *
     * @return object|\Kotchasan\Http\Response
     */
    protected function authorize(Request $request)
    {
        ApiController::validateMethod($request, 'GET');

        $login = $this->authenticateRequest($request);
        if (!$login) {
            return $this->errorResponse('Unauthorized', 401);
        }
        if (!ApiController::hasPermission($login, 'can_received_repair')) {
            return $this->errorResponse('Permission required', 403);
        }

        return $login;
    }

    /**
     * คำค้นที่ส่งมา
     *
     * @param Request $request
     *
     * @return string
     */
    protected function keyword(Request $request)
    {
        $search = $request->get('q')->topic();
        if ($search === '') {
            $search = $request->get('search')->topic();
        }

        return $search;
    }

    /**
     * จำนวนผลลัพธ์สูงสุด
     *
     * @param Request $request
     *
     * @return int
     */
    protected function limit(Request $request)
    {
        return min(50, max(1, $request->get('limit', 10)->toInt()));
    }
}
