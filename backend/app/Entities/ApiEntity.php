<?php

namespace App\Entities;

use App\Libraries\Camel;
use CodeIgniter\Entity\Entity;

/**
 * Base for every Entity returned by a Controller — serializes to camelCase
 * JSON so API responses match the camelCase request-body convention (§17).
 */
abstract class ApiEntity extends Entity
{
    public function jsonSerialize(): array
    {
        return Camel::keysToCamel(parent::jsonSerialize());
    }
}
