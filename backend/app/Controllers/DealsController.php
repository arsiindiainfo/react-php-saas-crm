<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\DealService;

class DealsController extends BaseApiController
{
    private DealService $deals;

    private const SORTABLE = ['name', 'valueAmount', 'stage', 'expectedCloseDate', 'createdAt', 'updatedAt'];

    public function __construct()
    {
        $this->deals = new DealService();
    }

    public function create()
    {
        $data = $this->validateBody('dealCreate');

        $deal = $this->deals->createDeal(
            (int) $data['companyId'],
            isset($data['contactId']) ? (int) $data['contactId'] : null,
            AuthContext::ownerScope(),
            $data['name'],
            (float) ($data['valueAmount'] ?? 0),
            $data['expectedCloseDate'] ?? null,
            $this->authUser()->id,
        );

        return $this->created($deal);
    }

    public function index()
    {
        $query     = $this->parseListQuery(self::SORTABLE, 'createdAt');
        $stage     = $this->request->getGet('stage');
        $companyId = $this->request->getGet('companyId');

        $result = $this->deals->listDeals(
            $query,
            AuthContext::ownerScope(),
            $stage ?: null,
            $companyId !== null ? (int) $companyId : null,
        );

        return $this->okPaginated($result['rows'], $query->page, $query->limit, $result['total']);
    }

    public function show($id)
    {
        return $this->ok($this->deals->getDeal((int) $id, AuthContext::ownerScope()));
    }

    public function update($id)
    {
        $data = $this->validateBody('dealUpdate');

        $fields = [];
        foreach (['name' => 'name', 'valueAmount' => 'value_amount', 'expectedCloseDate' => 'expected_close_date', 'contactId' => 'contact_id'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $fields[$column] = $data[$in];
            }
        }

        return $this->ok($this->deals->updateDeal((int) $id, $fields, AuthContext::ownerScope()));
    }

    public function changeStage($id)
    {
        $data = $this->validateBody('dealChangeStage');

        $deal = $this->deals->changeStage(
            (int) $id,
            AuthContext::ownerScope(),
            $data['stage'],
            $data['lostReason'] ?? null,
            $this->authUser()->id,
        );

        return $this->ok($deal);
    }
}
