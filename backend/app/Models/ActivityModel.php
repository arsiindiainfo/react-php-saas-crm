<?php

namespace App\Models;

use App\Entities\Activity;
use CodeIgniter\Model;

class ActivityModel extends Model
{
    protected $table         = 'activities';
    protected $primaryKey    = 'id';
    protected $returnType    = Activity::class;
    protected $useTimestamps = false;
    protected $allowedFields = ['type', 'subject', 'body', 'occurred_at', 'related_to_type', 'related_to_id', 'created_by'];

    /**
     * The embedded timeline component (§21) — newest first (§22.8).
     *
     * @return list<Activity>
     */
    public function timelineFor(string $relatedToType, int $relatedToId, ?string $typeFilter = null): array
    {
        $builder = $this->where('related_to_type', $relatedToType)->where('related_to_id', $relatedToId);

        if ($typeFilter !== null) {
            $builder->where('type', $typeFilter);
        }

        return $builder->orderBy('occurred_at', 'DESC')->findAll();
    }
}
