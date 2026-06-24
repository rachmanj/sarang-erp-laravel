@props(['name' => 'entity_filter'])

@php
    $entities = \App\Models\CompanyEntity::query()
        ->where(function ($query) {
            $query->where('is_active', true)->orWhereNull('is_active');
        })
        ->orderBy('code')
        ->get(['id', 'code', 'name']);

    $entityShortLabel = static function (\App\Models\CompanyEntity $entity): string {
        return match ($entity->code) {
            '71' => 'PT CSJ',
            '72' => 'CV Saranghae',
            default => $entity->name,
        };
    };
@endphp

<div {{ $attributes->merge(['class' => 'btn-group btn-group-sm btn-group-toggle']) }} data-toggle="buttons">
    <label class="btn btn-outline-secondary active mb-0">
        <input type="radio" name="{{ $name }}" id="entity-all" value="" checked> All
    </label>
    @foreach ($entities as $entity)
        <label class="btn btn-outline-secondary mb-0">
            <input type="radio" name="{{ $name }}" id="entity-{{ $entity->id }}" value="{{ $entity->id }}">
            {{ $entityShortLabel($entity) }}
        </label>
    @endforeach
</div>
