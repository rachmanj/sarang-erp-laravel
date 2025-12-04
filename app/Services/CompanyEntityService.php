<?php

namespace App\Services;

use App\Models\CompanyEntity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CompanyEntityService
{
    /**
     * Return all active entities ordered for dropdown usage.
     */
    public function getActiveEntities(): Collection
    {
        return CompanyEntity::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve the entity, defaulting to the primary active entity when none supplied.
     */
    public function getEntity(?int $entityId = null): CompanyEntity
    {
        if ($entityId) {
            return CompanyEntity::where('is_active', true)->findOrFail($entityId);
        }

        return $this->getDefaultEntity();
    }

    /**
     * Resolve entity from provided model fallback (expects company_entity_id relationship).
     */
    public function resolveFromModel(?int $entityId = null, ?Model $source = null): CompanyEntity
    {
        if ($source && $source->company_entity_id) {
            if ($source->relationLoaded('companyEntity') && $source->companyEntity) {
                return $source->companyEntity;
            }

            return $this->getEntity($source->company_entity_id);
        }

        return $this->getEntity($entityId);
    }

    /**
     * Return the ID of the resolved entity (helper for controllers).
     */
    public function resolveEntityId(?int $entityId = null, ?Model $source = null): int
    {
        return $this->resolveFromModel($entityId, $source)->id;
    }

    /**
     * Grab the default active entity (first active one in alphabetical order).
     */
    public function getDefaultEntity(): CompanyEntity
    {
        return CompanyEntity::where('is_active', true)
            ->orderBy('name')
            ->firstOrFail();
    }
}

