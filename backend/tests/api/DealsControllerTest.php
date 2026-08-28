<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class DealsControllerTest extends ApiTestCase
{
    private function createCompanyAndDeal($auth): array
    {
        $companyId = json_decode($auth->post('api/v1/companies', ['name' => 'Deal Target Inc'])->getJSON(), true)['data']['id'];
        $dealId    = json_decode(
            $auth->post('api/v1/deals', ['companyId' => $companyId, 'name' => 'Big Deal', 'valueAmount' => 1000])->getJSON(),
            true,
        )['data']['id'];

        return [$companyId, $dealId];
    }

    public function testCreateDealAgainstAnExistingCompany(): void
    {
        $auth              = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        [, $dealId]        = $this->createCompanyAndDeal($auth);

        $show = $auth->get('api/v1/deals/' . $dealId);
        $show->assertStatus(200);
        $this->assertSame('PROSPECTING', json_decode($show->getJSON(), true)['data']['stage']);
    }

    public function testMovingToLostRequiresAReason(): void
    {
        $auth       = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        [, $dealId] = $this->createCompanyAndDeal($auth);

        $noReason = $auth->post('api/v1/deals/' . $dealId . '/change-stage', ['stage' => 'LOST']);
        $noReason->assertStatus(400);
        $this->assertSame('LOST_REASON_REQUIRED', json_decode($noReason->getJSON(), true)['error']['code']);

        $ok = $auth->post('api/v1/deals/' . $dealId . '/change-stage', ['stage' => 'LOST', 'lostReason' => 'Budget cut']);
        $ok->assertStatus(200);
    }

    public function testWinningADealFlipsCompanyToCustomer(): void
    {
        $auth                = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        [$companyId, $dealId] = $this->createCompanyAndDeal($auth);

        $auth->post('api/v1/deals/' . $dealId . '/change-stage', ['stage' => 'WON'])->assertStatus(200);

        $company = $auth->get('api/v1/companies/' . $companyId);
        $this->assertSame('CUSTOMER', json_decode($company->getJSON(), true)['data']['status']);
    }

    public function testAClosedDealCannotChangeStageAgain(): void
    {
        $auth       = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        [, $dealId] = $this->createCompanyAndDeal($auth);

        $auth->post('api/v1/deals/' . $dealId . '/change-stage', ['stage' => 'WON'])->assertStatus(200);

        $again = $auth->post('api/v1/deals/' . $dealId . '/change-stage', ['stage' => 'PROPOSAL']);
        $again->assertStatus(409);
        $this->assertSame('INVALID_TRANSITION', json_decode($again->getJSON(), true)['error']['code']);
    }

    public function testDeletingACompanyWithOpenDealsIsBlocked(): void
    {
        $auth                = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        [$companyId]         = $this->createCompanyAndDeal($auth);

        $delete = $auth->delete('api/v1/companies/' . $companyId);
        $delete->assertStatus(409);
        $this->assertSame('COMPANY_HAS_OPEN_DEALS', json_decode($delete->getJSON(), true)['error']['code']);
    }
}
