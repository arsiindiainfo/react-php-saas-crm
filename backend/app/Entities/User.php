<?php

namespace App\Entities;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $email
 * @property string      $password_hash
 * @property string      $role
 * @property int|null    $manager_id
 * @property string      $status
 * @property string      $created_at
 * @property string      $updated_at
 */
class User extends ApiEntity
{
    protected $casts = [
        'id'         => 'integer',
        'manager_id' => '?integer',
    ];

    /** Never serialize the hash into an API response. */
    protected $hidden = ['password_hash'];

    public function isAdmin(): bool
    {
        return $this->attributes['role'] === 'ADMIN';
    }

    public function isManager(): bool
    {
        return $this->attributes['role'] === 'SALES_MANAGER';
    }
}
