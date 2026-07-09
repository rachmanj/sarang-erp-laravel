@php
    $visual = $cell['visual'] ?? ['primary' => 'missing', 'overlay' => null];
    $overlay = $visual['overlay'] ?? null;
    $primaryClass = ($visual['primary'] ?? 'missing') === 'present' ? 'koran-status-box--present' : 'koran-status-box--missing';
    $primaryIcon = ($visual['primary'] ?? 'missing') === 'present' ? 'fa-check' : 'fa-times';
@endphp
<td class="text-center p-1 koran-cell" style="cursor: pointer; min-width: 72px;"
    data-bank-account-id="{{ $cell['bank_account_id'] }}"
    data-month="{{ $cell['month'] }}"
    data-year="{{ $year }}"
    data-status="{{ $cell['status'] }}"
    @if ($cell['reconciliation_id']) data-reconciliation-id="{{ $cell['reconciliation_id'] }}" @endif
    title="{{ $cell['label'] }} — click for actions">
    <div class="koran-status-cell">
        <div class="koran-status-box {{ $primaryClass }}">
            <i class="fas {{ $primaryIcon }} koran-status-box-icon"></i>
            @if ($overlay)
                @if (! empty($overlay['href']))
                    <a href="{{ $overlay['href'] }}"
                        class="koran-status-overlay koran-status-overlay--{{ $overlay['color'] }} koran-status-overlay-link"
                        target="_blank"
                        rel="noopener noreferrer"
                        title="{{ $overlay['title'] }}"
                        onclick="event.stopPropagation();">
                        <i class="fas fa-{{ $overlay['icon'] }}"></i>
                    </a>
                @else
                    <span class="koran-status-overlay koran-status-overlay--{{ $overlay['color'] }}"
                        title="{{ $overlay['title'] }}">
                        <i class="fas fa-{{ $overlay['icon'] }}"></i>
                    </span>
                @endif
            @endif
        </div>
    </div>
</td>
