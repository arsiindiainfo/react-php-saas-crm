<?php

namespace App\Entities;

/**
 * @property int         $id
 * @property string      $name
 * @property string|null $industry
 * @property string|null $website
 * @property string|null $phone
 * @property string      $status
 * @property int         $owner_id
 * @property string      $created_at
 * @property string      $updated_at
 */
class Company extends ApiEntity
{
    protected $casts = [
        'id'       => 'integer',
        'owner_id' => 'integer',
    ];
}
