<?php
/**
 * @filesource modules/repair/controllers/detail.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Detail;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API รายละเอียดการซ่อมและประวัติการดำเนินการ
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends ApiController
{
    /**
     * GET api/repair/detail/get
     * อ่านรายละเอียดการซ่อม
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

            $index = Model::get($request->get('id', 0)->toInt());
            if ($index === null) {
                return $this->redirectResponse('/404', 'No data available', 404);
            }

            $statuses = \Repair\Status\Model::map(false);
            $canManage = ApiController::hasPermission($login, 'can_received_repair');

            $data = (object) [
                'id' => (int) $index->id,
                'job_id' => (string) $index->job_id,
                'name' => (string) $index->name,
                'phone' => (string) $index->phone,
                'address' => (string) $index->address,
                'province' => \Kotchasan\Province::get($index->provinceID),
                'zipcode' => (string) $index->zipcode,
                'equipment' => (string) $index->equipment,
                'serial' => (string) $index->serial,
                'job_description' => \Kotchasan\Text::untextarea((string) $index->job_description),
                'created_at' => $index->created_at,
                'appointment_date' => (string) $index->appointment_date,
                'appraiser' => (float) $index->appraiser,
                'appraiser_text' => empty($index->appraiser)
                    ? '-'
                    : \Kotchasan\Currency::format($index->appraiser).' '.\Kotchasan\Language::get('CURRENCY_UNITS', '', self::$cfg->currency_unit),
                'comment' => \Kotchasan\Text::untextarea((string) $index->comment),
                'status' => (int) $index->status,
                'status_text' => \Repair\Status\Model::topicOf($statuses, $index->status),
                'status_style' => 'background-color:'.\Repair\Status\Model::colorOf($statuses, $index->status),
                'can_manage' => $canManage ? 1 : 0,
                'print_url' => WEB_URL.'export.php?module=repair&typ=receipt&id='.rawurlencode($index->job_id),
                'track_url' => WEB_URL.'repair-track?id='.rawurlencode($index->job_id),
                'timeline' => [
                    'data' => Model::timeline($index->id, $canManage)
                ]
            ];

            return $this->successResponse([
                'data' => $data
            ], 'Repair details retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }

    /**
     * POST api/repair/detail/action
     * ลบประวัติการดำเนินการที่เลือก
     *
     * @param Request $request
     *
     * @return \Kotchasan\Http\Response
     */
    public function action(Request $request)
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

            if ($request->request('action')->filter('a-z_') !== 'delete') {
                return $this->errorResponse('Invalid action', 400);
            }

            $ids = $request->request('ids', [])->toInt();
            if (empty($ids)) {
                $ids = [$request->post('id')->toInt()];
            }

            $count = Model::removeStatus($ids);
            if (empty($count)) {
                return $this->errorResponse('Delete action failed', 400);
            }

            \Index\Log\Model::add(0, 'repair', 'Delete', 'Delete Transaction history ID(s) : '.implode(', ', $ids), $login->id);

            return $this->redirectResponse('reload', 'Deleted '.$count.' item(s) successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 500, $e);
        }
    }
}
