<header class="ed-header">
    <button type="button" class="ed-icon-button ed-menu-toggle" @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen" aria-controls="app-sidebar" aria-label="Buka navigasi"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>
    <div class="ed-header-title"><strong>Pusat Pengelolaan Dokumen OPD</strong><span>Pengelolaan dokumen perangkat daerah</span></div>
    <form action="{{ route('renja.index') }}" method="GET" class="ed-global-search" role="search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><input name="search" aria-label="Cari dokumen" placeholder="Cari dokumen, arsip…"><kbd>/</kbd></form>
    <div class="ed-header-actions">
        <form action="{{ route('set-ta') }}" method="POST" class="ed-year-form">@csrf<label for="active-year" class="sr-only">Tahun anggaran aktif</label><i class="fa-regular fa-calendar" aria-hidden="true"></i><select id="active-year" name="tahun_anggaran" onchange="this.form.submit()">@foreach(range(2020, 2040) as $year)<option value="{{ $year }}" @selected((int) session('active_ta', date('Y')) === $year)>TA {{ $year }}</option>@endforeach</select></form>
        <button type="button" class="ed-profile-button" onclick="document.getElementById('modal-profil-user').classList.remove('hidden')" aria-label="Lihat profil pengguna"><span class="ed-avatar">{{ mb_substr(auth()->user()->nama_lengkap ?? 'U', 0, 1) }}</span><span>{{ strtoupper(auth()->user()->role ?? 'Operator') }}</span></button>
        <form action="{{ route('logout') }}" method="POST">@csrf<button class="ed-icon-button ed-logout" type="submit" aria-label="Keluar akun" title="Keluar akun"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></button></form>
    </div>
</header>
