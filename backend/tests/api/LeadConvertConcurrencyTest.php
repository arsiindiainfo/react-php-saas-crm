<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * Proves the `SELECT ... FOR UPDATE` row lock in sp_lead_convert (§8.2):
 * two genuinely concurrent OS processes both call `CALL sp_lead_convert`
 * for the same lead. Exactly one must win; the loser must see
 * ALREADY_CONVERTED, and only one deal row may exist afterwards — the
 * "double-click creates duplicate companies/deals" risk from §30.
 *
 * @internal
 */
final class LeadConvertConcurrencyTest extends ApiTestCase
{
    public function testOnlyOneOfTwoConcurrentConvertsWins(): void
    {
        $auth   = $this->actingAs('arjun.rep@brightfield.test');
        $create = $auth->post('api/v1/leads', [
            'firstName' => 'Race', 'lastName' => 'Condition', 'companyName' => 'Race Co', 'source' => 'WEBSITE',
        ]);
        $leadId = json_decode($create->getJSON(), true)['data']['id'];
        $auth->put('api/v1/leads/' . $leadId, ['status' => 'CONTACTED']);
        $auth->put('api/v1/leads/' . $leadId, ['status' => 'QUALIFIED']);

        $dbConfig = config(\Config\Database::class)->tests;
        $params   = [
            $dbConfig['hostname'], $dbConfig['username'], $dbConfig['password'] ?: '', $dbConfig['database'], (string) $dbConfig['port'],
        ];

        $script = TESTPATH . '_support/scripts/convert_lead.php';

        $procA = proc_open(
            ['php', $script, ...$params, (string) $leadId, 'Race Co — Deal A'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipesA,
        );
        $procB = proc_open(
            ['php', $script, ...$params, (string) $leadId, 'Race Co — Deal B'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipesB,
        );

        $outA = stream_get_contents($pipesA[1]);
        $errA = stream_get_contents($pipesA[2]);
        fclose($pipesA[1]);
        fclose($pipesA[2]);
        proc_close($procA);

        $outB = stream_get_contents($pipesB[1]);
        $errB = stream_get_contents($pipesB[2]);
        fclose($pipesB[1]);
        fclose($pipesB[2]);
        proc_close($procB);

        $resultA = json_decode($outA, true);
        $resultB = json_decode($outB, true);

        $this->assertNotNull($resultA, "process A produced no JSON. stderr: {$errA}");
        $this->assertNotNull($resultB, "process B produced no JSON. stderr: {$errB}");

        $statuses = [$resultA['statusCode'], $resultB['statusCode']];
        sort($statuses);
        $this->assertSame(['ALREADY_CONVERTED', 'OK'], $statuses, 'exactly one convert call should win');

        $dealCount = $this->db->table('deals')->where('lead_id', $leadId)->countAllResults();
        $this->assertSame(1, $dealCount, 'only one deal must exist for the lead regardless of the race');
    }
}
