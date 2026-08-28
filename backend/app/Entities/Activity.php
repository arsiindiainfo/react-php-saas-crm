<?php

namespace App\Entities;

/**
 * @property int         $id
 * @property string      $type
 * @property string|null $subject
 * @property string      $body
 * @property string      $occurred_at
 * @property string      $related_to_type
 * @property int         $related_to_id
 * @property int         $created_by
 * @property string      $created_at
 */
class Activity extends ApiEntity
{
    protected $casts = [
        'id'            => 'integer',
        'related_to_id' => 'integer',
        'created_by'    => 'integer',
    ];
}
