<?php

namespace App\Entities;

/**
 * @property int         $id
 * @property int         $company_id
 * @property int|null    $contact_id
 * @property int|null    $lead_id
 * @property string      $name
 * @property string      $value_amount
 * @property string|null $expected_close_date
 * @property string      $stage
 * @property string|null $lost_reason
 * @property int         $owner_id
 * @property string|null $closed_at
 * @property string      $created_at
 * @property string      $updated_at
 */
class Deal extends ApiEntity
{
    protected $casts = [
        'id'         => 'integer',
        'company_id' => 'integer',
        'contact_id' => '?integer',
        'lead_id'    => '?integer',
        'owner_id'   => 'integer',
    ];
}
