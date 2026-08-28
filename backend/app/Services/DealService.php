<?php

namespace App\Services;

use App\Entities\Deal;
use App\Exceptions\ApiException;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\LostReasonRequiredException;
use App\Exceptions\RecordNotFoundException;
use App\Libraries\ListQuery;
use App\Models\DealModel;
use Config\Crm;

class DealService
{
    private DealModel $deals;
    private Crm $crm;

    public function __construct()
    {
        $this->deals = new DealModel();
        $this->crm   = config(Crm::class);
    }

    public function createDeal(
        int $companyId,
        ?int $contactId,
        ?array $ownerScope,
        string $name,
        float $valueAmount,
        ?string $expectedCloseDate,
        int $ownerId,
    ): Deal {
        $result = $this->deals->createViaProcedure($companyId, $contactId, $ownerScope, $name, $valueAmount, $expectedCloseDate, $ownerId);

        if ($result['statusCode'] === 'COMPANY_NOT_FOUND') {
            throw new RecordNotFoundException('company', 'COMPANY_NOT_FOUND');
        }

        if ($result['statusCode'] !== 'OK') {
            throw new ApiException($result['message'], 500, 'INTERNAL_ERROR');
        }

        return $this->deals->find($result['dealId']);
    }

    /**
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function listDeals(ListQuery $query, ?array $ownerScope, ?string $stage, ?int $companyId): array
    {
        return $this->deals->list($query, $ownerScope, $stage, $companyId);
    }

    public function getDeal(int $id, ?array $ownerScope): Deal
    {
        $deal = $this->deals->findScoped($id, $ownerScope);

        if ($deal === null) {
            throw new RecordNotFoundException('deal', 'DEAL_NOT_FOUND');
        }

        return $deal;
    }

    public function updateDeal(int $id, array $fields, ?array $ownerScope): Deal
    {
        $this->getDeal($id, $ownerScope);

        $allowed = array_intersect_key($fields, array_flip(['name', 'value_amount', 'expected_close_date', 'contact_id']));
        $this->deals->update($id, $allowed);

        return $this->deals->find($id);
    }

    public function changeStage(int $dealId, ?array $ownerScope, string $newStage, ?string $lostReason, int $changedBy): Deal
    {
        if (! in_array($newStage, $this->crm->dealStages, true)) {
            throw new ApiException('Unknown deal stage.', 400, 'VALIDATION_ERROR');
        }

        $result = $this->deals->changeStageViaProcedure($dealId, $ownerScope, $newStage, $lostReason, $changedBy);

        match ($result['statusCode']) {
            'NOT_FOUND'             => throw new RecordNotFoundException('deal', 'DEAL_NOT_FOUND'),
            'INVALID_TRANSITION'    => throw new InvalidTransitionException($result['message']),
            'LOST_REASON_REQUIRED'  => throw new LostReasonRequiredException(),
            'OK'                    => null,
            default                 => throw new ApiException($result['message'], 500, 'INTERNAL_ERROR'),
        };

        return $this->deals->find($dealId);
    }
}
