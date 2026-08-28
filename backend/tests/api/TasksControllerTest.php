<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class TasksControllerTest extends ApiTestCase
{
    public function testCreateAndCompleteATask(): void
    {
        $auth = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');

        $create = $auth->post('api/v1/tasks', ['subject' => 'Call back Nova', 'priority' => 'HIGH', 'dueDate' => '2026-09-01']);
        $create->assertStatus(201);
        $id = json_decode($create->getJSON(), true)['data']['id'];

        $complete = $auth->post('api/v1/tasks/' . $id . '/complete', []);
        $complete->assertStatus(200);
        $this->assertNotNull(json_decode($complete->getJSON(), true)['data']['completedAt']);

        // idempotent — completing again still succeeds
        $auth->post('api/v1/tasks/' . $id . '/complete', [])->assertStatus(200);
    }

    public function testDefaultListShowsOnlyMyOpenTasks(): void
    {
        $auth  = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        $meera = $this->withHeaders(['Authorization' => $this->bearerFor('meera.rep@brightfield.test')])->withBodyFormat('json');

        $auth->post('api/v1/tasks', ['subject' => 'Open task', 'priority' => 'LOW']);
        $doneId = json_decode(
            $auth->post('api/v1/tasks', ['subject' => 'Done task', 'priority' => 'LOW'])->getJSON(),
            true,
        )['data']['id'];
        $auth->post('api/v1/tasks/' . $doneId . '/complete', []);
        $meera->post('api/v1/tasks', ['subject' => "Meera's task", 'priority' => 'LOW']);

        $list = $auth->get('api/v1/tasks');
        $body = json_decode($list->getJSON(), true);

        $this->assertSame(1, $body['meta']['total']);
        $this->assertSame('Open task', $body['data'][0]['subject']);
    }

    public function testTaskLinkedToAnInvisibleRecordIs404(): void
    {
        $arjun = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])->withBodyFormat('json');
        $meera = $this->withHeaders(['Authorization' => $this->bearerFor('meera.rep@brightfield.test')])->withBodyFormat('json');

        $companyId = json_decode($meera->post('api/v1/companies', ['name' => "Meera's Co"])->getJSON(), true)['data']['id'];

        $result = $arjun->post('api/v1/tasks', [
            'subject' => 'Follow up', 'priority' => 'LOW', 'relatedToType' => 'COMPANY', 'relatedToId' => $companyId,
        ]);
        $result->assertStatus(404);
    }
}
