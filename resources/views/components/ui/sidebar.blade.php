@php
    $user = auth()->user();
    $oversight = $user && ($user->isAdmin() || $user->isVerifikator() || $user->isStaff());
@endphp
<aside id="app-sidebar" class="ed-sidebar" :class="{ 'is-open': sidebarOpen }" aria-label="Navigasi utama">
    <a class="ed-brand" href="{{ route('dashboard') }}"><span class="ed-brand-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span><span><strong>e-Dokumen</strong><small>Smart Template & Arsip</small></span></a>
    <button type="button" class="ed-sidebar-close" @click="sidebarOpen = false" aria-label="Tutup navigasi"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    <nav class="ed-nav">
        <p class="ed-nav-label">Utama</p>
        <x-ui.nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard', 'admin.dashboard', 'pimpinan.dashboard', 'staff.dashboard', 'verifikator.dashboard')" icon="fa-table-cells-large">Dashboard</x-ui.nav-link>
        @if(!$user?->isPimpinan())
        <p class="ed-nav-label">Kelola dokumen</p>
        <x-ui.nav-link :href="route('renja.index')" :active="request()->routeIs('renja.index', 'renja.show', 'admin.documents.*')" icon="fa-folder-open">{{ $user?->isOperator() ? 'Dokumen Saya' : 'Seluruh Dokumen' }}</x-ui.nav-link>
        <x-ui.nav-link :href="route('renja.workspace')" :active="request()->routeIs('renja.workspace', 'operator.renja-murni.*')" icon="fa-file-pen">Draft Dokumen</x-ui.nav-link>
        <x-ui.nav-link :href="route('renja.lampiran.index')" :active="request()->routeIs('renja.lampiran.*')" icon="fa-paperclip">Lampiran RENJA</x-ui.nav-link>
        <p class="ed-nav-label">Smart Template</p>
        @if($user?->isAdmin())
        <x-ui.nav-link :href="route('admin.templates.index')" :active="request()->routeIs('admin.templates.*')" icon="fa-layer-group">Template Dokumen</x-ui.nav-link>
        <x-ui.nav-link :href="route('admin.master_nomenklatur.index')" :active="request()->routeIs('admin.master_nomenklatur.*')" icon="fa-list-check">Master Nomenklatur</x-ui.nav-link>
        @elseif($user?->isOperator())
        <x-ui.nav-link :href="route('operator.templates.renja-murni')" :active="request()->routeIs('operator.templates.*')" icon="fa-layer-group">Template RENJA</x-ui.nav-link>
        @endif
        @can('viewAny', App\Models\ReferenceDocumentSchema::class)
        <x-ui.nav-link :href="route('reference-documents.index')" :active="request()->routeIs('reference-documents.*')" icon="fa-book-open">Dokumen Acuan</x-ui.nav-link>
        @endcan
        <p class="ed-nav-label">Kearsipan</p>
        <x-ui.nav-link :href="route('renja.fix.index')" :active="request()->routeIs('renja.fix.*')" icon="fa-folder-closed">Dokumen Disetujui</x-ui.nav-link>
        <x-ui.nav-link :href="route('renja.archive.index')" :active="request()->routeIs('renja.archive.*')" icon="fa-box-archive">Arsip Dokumen</x-ui.nav-link>
        <x-ui.nav-link :href="route('rkpd.index')" :active="request()->routeIs('rkpd.*')" icon="fa-file-lines">Dokumen RKPD</x-ui.nav-link>
        @endif
        @if($oversight)
        <p class="ed-nav-label">Verifikasi</p>
        <x-ui.nav-link :href="route('admin.verifikasi.index')" :active="request()->routeIs('admin.verifikasi.*', 'admin.review') && !in_array(request('status'), ['disetujui', 'perlu_revisi'])" icon="fa-clipboard-check">Antrean Verifikasi</x-ui.nav-link>
        <x-ui.nav-link :href="route('admin.verifikasi.index', ['status' => 'disetujui'])" :active="request()->routeIs('admin.verifikasi.index') && request('status') === 'disetujui'" icon="fa-circle-check">Disetujui</x-ui.nav-link>
        <x-ui.nav-link :href="route('admin.verifikasi.index', ['status' => 'perlu_revisi'])" :active="request()->routeIs('admin.verifikasi.index') && request('status') === 'perlu_revisi'" icon="fa-rotate-left">Perlu Revisi</x-ui.nav-link>
        <p class="ed-nav-label">Monitoring & Sistem</p>
        <x-ui.nav-link :href="route('admin.monitoring-opd.index')" :active="request()->routeIs('admin.monitoring-opd.*', 'bapperida.monitoring')" icon="fa-chart-column">Monitoring OPD</x-ui.nav-link>
        @if($user?->isAdmin())
        <x-ui.nav-link :href="route('admin.master_opd.index')" :active="request()->routeIs('admin.master_opd.*')" icon="fa-users">OPD & Pengguna</x-ui.nav-link>
        @endif
        @endif
        <button type="button" class="ed-nav-link" @click="settingsOpen = true; sidebarOpen = false"><i class="fa-solid fa-gear" aria-hidden="true"></i><span>Pengaturan Tampilan</span></button>
        <button type="button" class="ed-nav-link" onclick="document.getElementById('modal-panduan').classList.remove('hidden')"><i class="fa-solid fa-circle-question" aria-hidden="true"></i><span>Panduan Penggunaan</span></button>
    </nav>
    <div class="ed-sidebar-footer"><span class="ed-online-dot"></span><span>Bapperida Kabupaten Cirebon<small>Ruang kerja perangkat daerah</small></span></div>
</aside>
