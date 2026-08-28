<?php

namespace App\Entities;

/**
 * @property int         $id
 * @property string      $subject
 * @property string|null $due_date
 * @property string      $priority
 * @property string|null $related_to_type
 * @property int|null    $related_to_id
 * @property int         $assigned_to
 * @property int         $created_by
 * @property string|null $completed_at
 * @property string      $created_at
 * @property string      $updated_at
 */
class Task extends ApiEntity
{
    protected $casts = [
        'id'             => 'integer',
        'related_to_id'  => '?integer',
        'assigned_to'    => 'integer',
        'created_by'     => 'integer',
    ];
}
