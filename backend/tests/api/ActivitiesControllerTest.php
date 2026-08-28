<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class ActivitiesControllerTest extends ApiTestCase
{
    public function testLogAndListATimeline(): void
    {
        $auth      = $this->actingAs('arjun.rep@brightfield.test');
        $companyId = json_decode($auth->post('api/v1/companies', ['name' => 'Timeline Co'])->getJSON(), true)['data']['id'];

        $auth->post('api/v1/activities', [
            'type' => 'CALL', 'body' => 'Discussed pricing.', 'relatedToType' => 'COMPANY', 'relatedToId' => $companyId,
        ])->assertStatus(201);
        $auth->post('api/v1/activities', [
            'type' => 'NOTE', 'body' => 'Prefers email.', 'relatedToType' => 'COMPANY', 'relatedToId' => $companyId,
        ])->assertStatus(201);

        $all = $auth->get("api/v1/activities?relatedToType=COMPANY&relatedToId={$companyId}");
        $this->assertCount(2, json_decode($all->getJSON(), true)['data']);

        $notesOnly = $auth->get("api/v1/activities?relatedToType=COMPANY&relatedToId={$companyId}&type=NOTE");
        $notes     = json_decode($notesOnly->getJSON(), true)['data'];
        $this->assertCount(1, $notes);
        $this->assertSame('Prefers email.', $notes[0]['body']);
    }

    public function testCannotLogAgainstAnInvisibleRecord(): void
    {
        $arjun = $this->actingAs('arjun.rep@brightfield.test');
        $meera = $this->actingAs('meera.rep@brightfield.test');

        $companyId = json_decode($meera->post('api/v1/companies', ['name' => 'Private Co'])->getJSON(), true)['data']['id'];

        $result = $arjun->post('api/v1/activities', [
            'type' => 'NOTE', 'body' => 'Sneaky note', 'relatedToType' => 'COMPANY', 'relatedToId' => $companyId,
        ]);
        $result->assertStatus(404);
    }
}
