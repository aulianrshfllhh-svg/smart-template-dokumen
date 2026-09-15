<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\MasterOpd;
use App\Models\User;
use App\Models\MasterNomenklatur;
use App\Models\RenjaDocument;
use App\Models\RenjaTableEval;
use App\Models\RenjaTableUtama;
use App\Models\RenjaSection;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with official 71 Perangkat Daerah Kabupaten Cirebon.
     */
    public function run(): void
    {
        $allOpds = [
            1 => ['nama' => 'Sekretariat Daerah', 'romawi' => 'Lampiran I'],
            2 => ['nama' => 'Sekretariat Dewan Perwakilan Rakyat Daerah', 'romawi' => 'Lampiran II'],
            3 => ['nama' => 'Inspektorat', 'romawi' => 'Lampiran III'],
            4 => ['nama' => 'Dinas Pendidikan', 'romawi' => 'Lampiran IV'],
            5 => ['nama' => 'Dinas Kesehatan', 'romawi' => 'Lampiran V'],
            6 => ['nama' => 'Dinas Pekerjaan Umum dan Tata Ruang', 'romawi' => 'Lampiran VI'],
            7 => ['nama' => 'Dinas Perumahan, Kawasan Permukiman dan Pertanahan', 'romawi' => 'Lampiran VII'],
            8 => ['nama' => 'Dinas Pemadam Kebakaran dan Penyelamatan', 'romawi' => 'Lampiran VIII'],
            9 => ['nama' => 'Satuan Polisi Pamong Praja', 'romawi' => 'Lampiran IX'],
            10 => ['nama' => 'Dinas Sosial', 'romawi' => 'Lampiran X'],
            11 => ['nama' => 'Dinas Ketenagakerjaan', 'romawi' => 'Lampiran XI'],
            12 => ['nama' => 'Dinas Pengendalian Penduduk, Keluarga Berencana, Pemberdayaan Perempuan dan Perlindungan Anak', 'romawi' => 'Lampiran XII'],
            13 => ['nama' => 'Dinas Lingkungan Hidup', 'romawi' => 'Lampiran XIII'],
            14 => ['nama' => 'Dinas Kependudukan dan Pencatatan Sipil', 'romawi' => 'Lampiran XIV'],
            15 => ['nama' => 'Dinas Perhubungan', 'romawi' => 'Lampiran XV'],
            16 => ['nama' => 'Dinas Komunikasi dan Informatika', 'romawi' => 'Lampiran XVI'],
            17 => ['nama' => 'Dinas Kebudayaan dan Pariwisata', 'romawi' => 'Lampiran XVII'],
            18 => ['nama' => 'Dinas Pemuda dan Olahraga', 'romawi' => 'Lampiran XVIII'],
            19 => ['nama' => 'Dinas Pertanian', 'romawi' => 'Lampiran XIX'],
            20 => ['nama' => 'Dinas Ketahanan Pangan dan Perikanan', 'romawi' => 'Lampiran XX'],
            21 => ['nama' => 'Dinas Perdagangan dan Perindustrian', 'romawi' => 'Lampiran XXI'],
            22 => ['nama' => 'Dinas Koperasi dan Usaha Kecil dan Menengah', 'romawi' => 'Lampiran XXII'],
            23 => ['nama' => 'Dinas Kearsipan dan Perpustakaan', 'romawi' => 'Lampiran XXIII'],
            24 => ['nama' => 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu', 'romawi' => 'Lampiran XXIV'],
            25 => ['nama' => 'Dinas Pemberdayaan Masyarakat dan Desa', 'romawi' => 'Lampiran XXV'],
            26 => ['nama' => 'Badan Kepegawaian dan Pengembangan Sumber Daya Manusia', 'romawi' => 'Lampiran XXVI'],
            27 => ['nama' => 'Badan Perencanaan Pembangunan, Penelitian dan Pengembangan Daerah', 'romawi' => 'Lampiran XXVII'],
            28 => ['nama' => 'Badan Keuangan dan Aset Daerah', 'romawi' => 'Lampiran XXVIII'],
            29 => ['nama' => 'Badan Pendapatan Daerah', 'romawi' => 'Lampiran XXIX'],
            30 => ['nama' => 'Badan Kesatuan Bangsa dan Politik', 'romawi' => 'Lampiran XXX'],
            31 => ['nama' => 'Badan Penanggulangan Bencana Daerah', 'romawi' => 'Lampiran XXXI'],
            32 => ['nama' => 'Kecamatan Arjawinangun', 'romawi' => 'Lampiran XXXII'],
            33 => ['nama' => 'Kecamatan Astanajapura', 'romawi' => 'Lampiran XXXIII'],
            34 => ['nama' => 'Kecamatan Babakan', 'romawi' => 'Lampiran XXXIV'],
            35 => ['nama' => 'Kecamatan Beber', 'romawi' => 'Lampiran XXXV'],
            36 => ['nama' => 'Kecamatan Ciledug', 'romawi' => 'Lampiran XXXVI'],
            37 => ['nama' => 'Kecamatan Ciwaringin', 'romawi' => 'Lampiran XXXVII'],
            38 => ['nama' => 'Kecamatan Depok', 'romawi' => 'Lampiran XXXVIII'],
            39 => ['nama' => 'Kecamatan Dukupuntang', 'romawi' => 'Lampiran XXXIX'],
            40 => ['nama' => 'Kecamatan Gebang', 'romawi' => 'Lampiran XL'],
            41 => ['nama' => 'Kecamatan Gegesik', 'romawi' => 'Lampiran XLI'],
            42 => ['nama' => 'Kecamatan Gempol', 'romawi' => 'Lampiran XLII'],
            43 => ['nama' => 'Kecamatan Greged', 'romawi' => 'Lampiran XLIII'],
            44 => ['nama' => 'Kecamatan Gunungjati', 'romawi' => 'Lampiran XLIV'],
            45 => ['nama' => 'Kecamatan Jamblang', 'romawi' => 'Lampiran XLV'],
            46 => ['nama' => 'Kecamatan Kaliwedi', 'romawi' => 'Lampiran XLVI'],
            47 => ['nama' => 'Kecamatan Kapetakan', 'romawi' => 'Lampiran XLVII'],
            48 => ['nama' => 'Kecamatan Karangsembung', 'romawi' => 'Lampiran XLVIII'],
            49 => ['nama' => 'Kecamatan Karangwareng', 'romawi' => 'Lampiran XLIX'],
            50 => ['nama' => 'Kecamatan Kedawung', 'romawi' => 'Lampiran L'],
            51 => ['nama' => 'Kecamatan Klangenan', 'romawi' => 'Lampiran LI'],
            52 => ['nama' => 'Kecamatan Lemahabang', 'romawi' => 'Lampiran LII'],
            53 => ['nama' => 'Kecamatan Losari', 'romawi' => 'Lampiran LIII'],
            54 => ['nama' => 'Kecamatan Mundu', 'romawi' => 'Lampiran LIV'],
            55 => ['nama' => 'Kecamatan Pabedilan', 'romawi' => 'Lampiran LV'],
            56 => ['nama' => 'Kecamatan Pabuaran', 'romawi' => 'Lampiran LVI'],
            57 => ['nama' => 'Kecamatan Palimanan', 'romawi' => 'Lampiran LVII'],
            58 => ['nama' => 'Kecamatan Pangenan', 'romawi' => 'Lampiran LVIII'],
            59 => ['nama' => 'Kecamatan Panguragan', 'romawi' => 'Lampiran LIX'],
            60 => ['nama' => 'Kecamatan Pasaleman', 'romawi' => 'Lampiran LX'],
            61 => ['nama' => 'Kecamatan Plered', 'romawi' => 'Lampiran LXI'],
            62 => ['nama' => 'Kecamatan Plumbon', 'romawi' => 'Lampiran LXII'],
            63 => ['nama' => 'Kecamatan Sedong', 'romawi' => 'Lampiran LXIII'],
            64 => ['nama' => 'Kecamatan Sumber', 'romawi' => 'Lampiran LXIV'],
            65 => ['nama' => 'Kecamatan Suranenggala', 'romawi' => 'Lampiran LXV'],
            66 => ['nama' => 'Kecamatan Susukan', 'romawi' => 'Lampiran LXVI'],
            67 => ['nama' => 'Kecamatan Susukanlebak', 'romawi' => 'Lampiran LXVII'],
            68 => ['nama' => 'Kecamatan Talun', 'romawi' => 'Lampiran LXVIII'],
            69 => ['nama' => 'Kecamatan Tengahtani', 'romawi' => 'Lampiran LXIX'],
            70 => ['nama' => 'Kecamatan Waled', 'romawi' => 'Lampiran LXX'],
            71 => ['nama' => 'Kecamatan Weru', 'romawi' => 'Lampiran LXXI'],
        ];

        $firstOpdModel = null;

        foreach ($allOpds as $num => $info) {
            $romanNumeral = strtoupper(trim(str_ireplace('Lampiran', '', $info['romawi'])));
            $created = MasterOpd::updateOrCreate(
                ['kode_opd' => sprintf('1.%02d.0.00.0.00.01.0000', $num)],
                [
                    'nama_opd' => $info['nama'],
                    'lampiran_number' => $num,
                    'nomor_lampiran_romawi' => $romanNumeral,
                    'jenis_lampiran_default' => 'PERATURAN BUPATI CIREBON',
                ]
            );

            if ($num === 6) {
                $firstOpdModel = $created;
            }

            // Seed Akun Operator untuk setiap Perangkat Daerah (OPD)
            $slugName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $info['nama']));
            User::updateOrCreate(
                ['username_nip' => 'opd_' . sprintf('%02d', $num)],
                [
                    'name' => 'Operator ' . $info['nama'],
                    'nama_lengkap' => 'Operator Renja ' . $info['nama'],
                    'email' => 'operator.' . $slugName . '@cirebonkab.go.id',
                    'password' => Hash::make('password'),
                    'role' => 'operator',
                    'opd_id' => $created->id,
                ]
            );
        }

        if (!$firstOpdModel) {
            $firstOpdModel = MasterOpd::first();
        }

        $bapperidaOpd = MasterOpd::where('nama_opd', 'LIKE', '%Badan Perencanaan Pembangunan%')
            ->orWhere('nama_opd', 'LIKE', '%bapperida%')
            ->first();

        // 2. Seed Users dengan 3 Role Utama (RBAC)
        User::updateOrCreate(
            ['username_nip' => 'admin'],
            [
                'name' => 'Administrator Bapperida',
                'nama_lengkap' => 'Administrator Bapperida (ADMIN)',
                'email' => 'admin@cirebonkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'opd_id' => $bapperidaOpd?->id,
            ]
        );

        User::updateOrCreate(
            ['username_nip' => 'verifikator'],
            [
                'name' => 'Tim Verifikator Perencana Bapperida',
                'nama_lengkap' => 'Tim Verifikator Bapperida',
                'email' => 'verifikator@cirebonkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'verifikator',
                'opd_id' => $bapperidaOpd?->id,
            ]
        );

        User::updateOrCreate(
            ['username_nip' => '198501012010011001'],
            [
                'name' => 'Operator Renja Dinas PUPR',
                'nama_lengkap' => 'Operator Renja Dinas PUPR',
                'email' => 'operator.pupr@cirebonkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'opd_id' => $firstOpdModel?->id,
            ]
        );

        $depokOpd = MasterOpd::where('nama_opd', 'LIKE', '%Kecamatan Depok%')->first();

        User::updateOrCreate(
            ['username_nip' => 'operator_depok'],
            [
                'name' => 'Operator Renja Kecamatan Depok',
                'nama_lengkap' => 'Operator Renja Kecamatan Depok',
                'email' => 'operator.depok@cirebonkab.go.id',
                'password' => Hash::make('password'),
                'role' => 'operator',
                'opd_id' => $depokOpd?->id,
            ]
        );

        // 3. Seed Master Nomenklatur
        MasterNomenklatur::create([
            'kode_rekening' => '1.03.01.2.01',
            'nama_nomenklatur' => 'Program Pengelolaan Sumber Daya Air (SDA)',
            'level' => 'program',
            'tahun_anggaran' => 2027,
        ]);

        // 4. Official Narasi Content based on Perbup Cirebon PDF Sample
        $latarBelakang = '<p>Sesuai dengan Peraturan Menteri Dalam Negeri Republik Indonesia Nomor 86 Tahun 2017 tentang Tata Cara Perencanaan, Pengendalian dan Evaluasi Pembangunan Daerah tentang Rencana Pembangunan Jangka Panjang Daerah dan Rencana Pembangunan Jangka Menengah Daerah, Serta Tata Cara Perubahan Rencana Pembangunan Jangka Panjang Daerah, Rencana Pembangunan Jangka Menengah Daerah dan Rencana Kerja Pemerintah Daerah (Berita Negara Republik Indonesia Tahun 2017 Nomor 1312); bahwa setiap Satuan Kerja Perangkat Daerah (SKPD) harus menyusun Rencana Kerja SKPD (Renja SKPD). Rencana Kerja Perangkat Daerah Tahun 2027 adalah dokumen perencanaan SKPD untuk periode 1 (satu) tahun. Rencana Kerja memuat program, kegiatan dan Sub Kegiatan, lokasi kegiatan, evaluasi pelaksanaan program dan kegiatan tahun sebelumnya, indikator kinerja, kelompok sasaran serta pagu indikatif dan prakiraan maju dana yang dibutuhkan untuk tahun 2027.</p>';

        $landasanHukum = '<p>Landasan Hukum dalam penyusunan Rencana Kerja Perangkat Daerah adalah Undang-Undang Nomor 17 Tahun 2003, Undang-Undang Nomor 23 Tahun 2014, Permendagri Nomor 86 Tahun 2017, Permendagri Nomor 90 Tahun 2019, dan Peraturan Bupati Cirebon terkait.</p>';

        $maksudTujuan = '<p>Maksud penyusunan Renja adalah sebagai pedoman operasional 1 (satu) tahunan. Tujuan adalah mengarahkan pelaksanaan program agar sinkron dengan sasaran Pemerintah Kabupaten Cirebon.</p>';

        $sistematika = '<p>Dokumen disusun dengan sistematika Bab I Pendahuluan, Bab II Hasil Evaluasi, Bab III Tujuan & Sasaran, Bab IV Rencana Kerja, Bab V Target Kinerja, dan Bab VI Penutup.</p>';

        $penutupNarasi = '<p>Rencana kerja ini merupakan pedoman tugas dan komitmen pimpinan beserta staf dalam mewujudkan sasaran strategis pembangunan Kabupaten Cirebon.</p>';

        $doc = RenjaDocument::create([
            'opd_id' => $firstOpdModel->id,
            'tahun_anggaran' => 2027,
            'jenis_dokumen' => 'Rencana Kerja (Renja)',
            'status' => 'submitted',
            'latar_belakang' => $latarBelakang,
            'landasan_hukum' => $landasanHukum,
            'maksud_tujuan' => $maksudTujuan,
            'sistematika' => $sistematika,
            'evaluasi_narasi' => '<p>Evaluasi pelaksanaan program dan kegiatan tahun sebelumnya mencapai 94.46%.</p>',
            'penutup_narasi' => $penutupNarasi,
        ]);

        // Seed Renja Sections
        $sections = [
            ['bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.1', 'sub_bab_title' => 'Latar Belakang', 'content' => $latarBelakang, 'guidance_text' => 'Paparkan latar belakang penyusunan Renja.', 'is_completed' => true, 'order_index' => 10],
            ['bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.2', 'sub_bab_title' => 'Landasan Hukum', 'content' => $landasanHukum, 'guidance_text' => 'Cantumkan landasan hukum regulasi.', 'is_completed' => true, 'order_index' => 20],
            ['bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.3', 'sub_bab_title' => 'Maksud dan Tujuan', 'content' => $maksudTujuan, 'guidance_text' => 'Paparkan maksud dan tujuan.', 'is_completed' => true, 'order_index' => 30],
            ['bab_code' => 'BAB I', 'bab_title' => 'Pendahuluan', 'sub_bab_code' => '1.4', 'sub_bab_title' => 'Sistematika Penulisan', 'content' => $sistematika, 'guidance_text' => 'Uraikan sistematika penulisan Bab I-VI.', 'is_completed' => true, 'order_index' => 40],
            ['bab_code' => 'BAB VI', 'bab_title' => 'Penutup', 'sub_bab_code' => '6.1', 'sub_bab_title' => 'Kesimpulan & Saran Penutup', 'content' => $penutupNarasi, 'guidance_text' => 'Paparkan narasi penutup.', 'is_completed' => true, 'order_index' => 50],
        ];

        foreach ($sections as $s) {
            $s['document_id'] = $doc->id;
            RenjaSection::create($s);
        }
    }
}
