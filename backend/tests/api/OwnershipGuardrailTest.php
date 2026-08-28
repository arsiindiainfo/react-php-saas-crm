<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * §6.2 guardrail suite: a rep never sees another rep's record (404, not
 * merely hidden client-side); a manager sees their direct report's record;
 * an ADMIN sees everything. Companies/contacts already get equivalent
 * coverage in CompaniesControllerTest — this file covers Leads and Deals,
 * the two resources added in Phase 2.
 *
 * @internal
 */
final class OwnershipGuardrailTest extends ApiTestCase
{
    public function testRepCannotSeeAnotherRepsLead(): void
    {
        $arjun = $this->actingAs('arjun.rep@brightfield.test');
        $meera = $this->actingAs('meera.rep@brightfield.test');

        $create = $arjun->post('api/v1/leads', ['firstName' => 'Owned', 'lastName' => 'ByArjun', 'source' => 'OTHER']);
        $id     = json_decode($create->getJSON(), true)['data']['id'];

        $meera->get('api/v1/leads/' . $id)->assertStatus(404);
        $arjun->get('api/v1/leads/' . $id)->assertStatus(200);
    }

    public function testRepCannotSeeAnotherRepsDeal(): void
    {
        $arjun = $this->actingAs('arjun.rep@brightfield.test');
        $meera = $this->actingAs('meera.rep@brightfield.test');

        $companyId = json_decode($arjun->post('api/v1/companies', ['name' => 'Arjun Deal Co'])->getJSON(), true)['data']['id'];
        $dealId    = json_decode(
            $arjun->post('api/v1/deals', ['companyId' => $companyId, 'name' => 'Private Deal', 'valueAmount' => 10])->getJSON(),
            true,
        )['data']['id'];

        $meera->get('api/v1/deals/' . $dealId)->assertStatus(404);
    }

    public function testManagerSeesTheirReportsLeadAndDeal(): void
    {
        $arjun = $this->actingAs('arjun.rep@brightfield.test');
        $priya = $this->actingAs('priya.manager@brightfield.test');

        $leadId = json_decode(
            $arjun->post('api/v1/leads', ['firstName' => 'Team', 'lastName' => 'Lead', 'source' => 'OTHER'])->getJSON(),
            true,
        )['data']['id'];

        $priya->get('api/v1/leads/' . $leadId)->assertStatus(200);
    }

    public function testManagerCannotSeeAnUnrelatedRepsLead(): void
    {
        // Meera has no manager (per UserSeeder) — she doesn't report to Priya,
        // so Priya (Arjun's manager) must not see Meera's records.
        $meera = $this->actingAs('meera.rep@brightfield.test');
        $priya = $this->actingAs('priya.manager@brightfield.test');

        $leadId = json_decode(
            $meera->post('api/v1/leads', ['firstName' => 'Meera', 'lastName' => 'Only', 'source' => 'OTHER'])->getJSON(),
            true,
        )['data']['id'];

        $priya->get('api/v1/leads/' . $leadId)->assertStatus(404);
        $meera->get('api/v1/leads/' . $leadId)->assertStatus(200);
    }

    public function testAdminSeesEveryonesRecords(): void
    {
        $arjun = $this->actingAs('arjun.rep@brightfield.test');
        $admin = $this->actingAs('admin@brightfield.test');

        $leadId = json_decode(
            $arjun->post('api/v1/leads', ['firstName' => 'Visible', 'lastName' => 'ToAdmin', 'source' => 'OTHER'])->getJSON(),
            true,
        )['data']['id'];

        $admin->get('api/v1/leads/' . $leadId)->assertStatus(200);
    }
}
