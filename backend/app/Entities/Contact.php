<?php

namespace App\Entities;

/**
 * @property int         $id
 * @property int         $company_id
 * @property string      $first_name
 * @property string      $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $job_title
 * @property int         $owner_id
 * @property string      $created_at
 * @property string      $updated_at
 */
class Contact extends ApiEntity
{
    protected $casts = [
        'id'         => 'integer',
        'company_id' => 'integer',
        'owner_id'   => 'integer',
    ];
}
