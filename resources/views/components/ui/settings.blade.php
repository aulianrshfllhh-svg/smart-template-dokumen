<div x-show="settingsOpen" x-cloak class="ed-dialog-backdrop" @keydown.escape.window="settingsOpen = false" @click.self="settingsOpen = false">
    <section class="ed-settings ed-panel" role="dialog" aria-modal="true" aria-labelledby="settings-title" x-ref="settingsPanel" tabindex="-1">
        <div class="ed-section-heading"><div><span class="ed-eyebrow">Preferensi pribadi</span><h2 id="settings-title">Pengaturan Tampilan</h2><p>Sesuaikan kenyamanan ruang kerja pada browser ini.</p></div><button class="ed-icon-button" type="button" @click="settingsOpen = false" aria-label="Tutup pengaturan"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>
        <label class="ed-setting-row"><span><strong>Tampilan tabel ringkas</strong><small>Kurangi jarak antarbaris untuk melihat lebih banyak data.</small></span><input type="checkbox" x-model="compactTables" @change="savePreferences()"></label>
        <label class="ed-setting-row"><span><strong>Kurangi animasi</strong><small>Batasi gerakan dan transisi antarmuka.</small></span><input type="checkbox" x-model="reduceMotion" @change="savePreferences()"></label>
        <div class="ed-settings-footer"><span>Preferensi tersimpan otomatis di browser.</span><button class="st-btn st-btn-primary" type="button" @click="settingsOpen = false">Selesai</button></div>
    </section>
</div>
