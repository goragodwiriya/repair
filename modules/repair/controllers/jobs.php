<?php
/**
 * @filesource modules/repair/controllers/jobs.php
 *
 * @copyright 2026 Goragod.com
 * @license https://www.kotchasan.com/license/
 *
 * @see https://www.kotchasan.com/
 */

namespace Repair\Jobs;

use Gcms\Api as ApiController;
use Kotchasan\Http\Request;

/**
 * API ตารางรายการซ่อม (เจ้าหน้าที่รับซ่อมและช่างซ่อม)
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Controller extends \Gcms\Table
{
    /**
     * คอลัมน์ที่เรียงลำดับได้ (ป้องกัน SQL injection)
     *
     * @var array
     */
    protected $allowedSortColumns = ['id', 'job_id', 'name', 'created_at', 'appointment_date', 'status'];

    /**
     * ตรวจสอบสิทธิ์
     *
     * @param Request $request
     * @param object $login
     *
     * @return mixed
     */
    protected function checkAuthorization(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, ['can_received_repair', 'can_repair'])) {
            return $this->errorResponse('Permission required', 403);
        }

        return true;
    }

    /**
     * ตัวเลือกสำหรับกรองข้อมูล
     *
     * @param Request $request
     * @param object $login
     *
     * @return array
     */
    protected function getCustomParams(Request $request, $login): array
    {
        $params = [
            'from' => $request->get('from')->date(),
            'to' => $request->get('to')->date(),
            'status' => $request->get('status', -1)->toInt()
        ];

        if (ApiController::hasPermission($login, 'can_received_repair')) {
            // เจ้าหน้าที่รับซ่อม เลือกดูช่างคนใดก็ได้
            $params['operator_id'] = $request->get('operator_id')->toInt();
        } else {
            // ช่างซ่อม เห็นเฉพาะงานที่ยังไม่มอบหมาย และงานของตัวเอง
            $params['operator_id'] = [0, (int) $login->id];
        }

        return $params;
    }

    /**
     * Query ข้อมูลสำหรับส่งให้กับ DataTable
     *
     * @param array $params
     * @param object|null $login
     *
     * @return \Kotchasan\QueryBuilder\QueryBuilderInterface
     */
    protected function toDataTable($params, $login = null)
    {
        return Model::toDataTable($params);
    }

    /**
     * ตัวเลือกของ filter
     *
     * @param array $params
     * @param object|null $login
     *
     * @return array
     */
    protected function getFilters($params, $login = null)
    {
        $operators = \Repair\Operator\Model::toOptions();

        // ช่างซ่อมที่ไม่ใช่เจ้าหน้าที่รับซ่อม เลือกได้เฉพาะตัวเอง
        if (!ApiController::hasPermission($login, 'can_received_repair')) {
            $operators = array_values(array_filter($operators, function ($item) use ($login) {
                return (int) $item['value'] === (int) $login->id;
            }));
        }

        return [
            'operator_id' => $operators,
            'status' => \Repair\Status\Model::toOptions()
        ];
    }

    /**
     * จัดรูปแบบข้อมูลก่อนส่งให้ตาราง
     *
     * @param array $datas
     * @param object|null $login
     *
     * @return array
     */
    protected function formatDatas(array $datas, $login = null): array
    {
        $statuses = \Repair\Status\Model::map(false);
        $operators = \Repair\Operator\Model::map();
        $canManage = ApiController::hasPermission($login, 'can_received_repair') ? 1 : 0;

        foreach ($datas as $item) {
            $item->status_text = \Repair\Status\Model::topicOf($statuses, $item->status);
            $item->status_color = \Repair\Status\Model::colorOf($statuses, $item->status);
            $item->operator_name = isset($operators[(int) $item->operator_id]) ? $operators[(int) $item->operator_id] : '';
            $item->can_manage = $canManage;
            $item->print_url = WEB_URL.'export.php?module=repair&typ=receipt&id='.rawurlencode($item->job_id);
        }

        return $datas;
    }

    /**
     * ลบรายการซ่อมที่เลือก
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDeleteAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_received_repair'])) {
            return $this->errorResponse('Failed to process request', 403);
        }

        $ids = $request->request('ids', [])->toInt();
        if (empty($ids)) {
            $ids = [$request->post('id')->toInt()];
        }
        $ids = array_values(array_filter($ids));
        if (empty($ids)) {
            return $this->errorResponse('No items selected', 400);
        }

        $removeCount = Model::remove($ids);
        if (empty($removeCount)) {
            return $this->errorResponse('Delete action failed', 400);
        }

        \Index\Log\Model::add(0, 'repair', 'Delete', 'Delete Repair ID(s) : '.implode(', ', $ids), $login->id);

        return $this->redirectResponse('reload', 'Deleted '.$removeCount.' item(s) successfully', 200, 0, 'table');
    }

    /**
     * เปิดหน้ารายละเอียดการซ่อม
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleDetailAction(Request $request, $login)
    {
        return $this->redirectResponse('/repair-detail?id='.$request->post('id')->toInt());
    }

    /**
     * เปิดหน้าแก้ไขใบรับซ่อม
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleEditAction(Request $request, $login)
    {
        if (!ApiController::hasPermission($login, 'can_received_repair')) {
            return $this->errorResponse('Permission required', 403);
        }

        return $this->redirectResponse('/repair-receive?id='.$request->post('id')->toInt());
    }

    /**
     * เปิดฟอร์มปรับปรุงสถานะการซ่อม
     *
     * @param Request $request
     * @param object $login
     *
     * @return \Kotchasan\Http\Response
     */
    protected function handleStatusAction(Request $request, $login)
    {
        if (!ApiController::canModify($login, ['can_received_repair', 'can_repair'])) {
            return $this->errorResponse('Failed to process request', 403);
        }

        $payload = \Repair\Action\Model::modalPayload($request->post('id')->toInt(), $login);
        if ($payload === null) {
            return $this->errorResponse('No data available', 404);
        }

        return $this->successResponse($payload, 'Repair status form');
    }
}
