@props(['name' => 'entity_filter'])

<div {{ $attributes->merge(['class' => 'btn-group btn-group-sm btn-group-toggle']) }} data-toggle="buttons">
    <label class="btn btn-outline-secondary active mb-0">
        <input type="radio" name="{{ $name }}" id="entity-all" value="" checked> All
    </label>
    @if ($ptCahaya ?? null)
        <label class="btn btn-outline-secondary mb-0">
            <input type="radio" name="{{ $name }}" id="entity-pt" value="{{ $ptCahaya->id }}"> PT CSJ
        </label>
    @endif
    @if ($cvCahaya ?? null)
        <label class="btn btn-outline-secondary mb-0">
            <input type="radio" name="{{ $name }}" id="entity-cv" value="{{ $cvCahaya->id }}"> CV Saranghae
        </label>
    @endif
</div>
