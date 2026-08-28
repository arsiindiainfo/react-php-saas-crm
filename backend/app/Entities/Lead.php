<?php

namespace App\Entities;

/**
 * @property int         $id
 * @property string      $first_name
 * @property string      $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $company_name
 * @property string      $source
 * @property string      $status
 * @property string|null $disqualify_reason
 * @property int         $owner_id
 * @property string|null $converted_at
 * @property int|null    $converted_company_id
 * @property int|null    $converted_contact_id
 * @property int|null    $converted_deal_id
 * @property string      $created_at
 * @property string      $updated_at
 */
class Lead extends ApiEntity
{
    protected $casts = [
        'id'                   => 'integer',
        'owner_id'             => 'integer',
        'converted_company_id' => '?integer',
        'converted_contact_id' => '?integer',
        'converted_deal_id'    => '?integer',
    ];
}
