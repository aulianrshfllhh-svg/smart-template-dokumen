@props(['label' => null, 'status' => '', 'tone' => null])
@php
    $resolvedTone = $tone ?? match ($status) {
        'disetujui', 'approved', 'final', 'dikunci', 'active' => 'green',
        'perlu_revisi', 'revisi', 'revision', 'revision_required', 'rejected' => 'red',
        'menunggu_verifikasi', 'submitted', 'menunggu_pemeriksaan', 'dikirim_ulang' => 'blue',
        'sedang_diperiksa', 'under_review', 'under_verification', 'review' => 'purple',
        default => 'slate',
    };
@endphp
<span {{ $attributes->class(['ed-badge', 'ed-tone-'.$resolvedTone]) }}><span class="ed-badge-dot" aria-hidden="true"></span>{{ $label ?? str_replace('_', ' ', ucfirst($status)) }}{{ $slot }}</span>
