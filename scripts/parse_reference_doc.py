#!/usr/bin/env python3
"""
parse_reference_doc.py — Auto-Detect Modul dari Dokumen Acuan
e-Renja Bapperida Kabupaten Cirebon

CLI Usage:
    python parse_reference_doc.py /path/to/dokumen.docx

Output:
    JSON ke stdout dengan struktur BAB, Sub-Bab, dan Tabel yang terdeteksi.
    Exit code 0 = sukses, 1 = error (file tidak valid / struktur tidak ditemukan).

Error output ke stderr.
"""

import sys
import os
import re
import json
import site

# ---------------------------------------------------------------------------
# Dynamically resolve user site-packages path (sama seperti formatter.py)
# ---------------------------------------------------------------------------
user_site = site.getusersitepackages()
if user_site and user_site not in sys.path and os.path.exists(user_site):
    sys.path.insert(0, user_site)

appdata = os.environ.get('APPDATA')
if appdata:
    for py_ver in ['Python314', 'Python313', 'Python312', 'Python311', 'Python310', 'Python39']:
        roaming_site = os.path.join(appdata, 'Python', py_ver, 'site-packages')
        if os.path.exists(roaming_site) and roaming_site not in sys.path:
            sys.path.insert(0, roaming_site)

try:
    import docx
    from docx.oxml.ns import qn
except ImportError as e:
    print(json.dumps({
        "error": True,
        "message": f"Dependensi python-docx tidak ditemukan: {e}. Jalankan: pip install python-docx"
    }), file=sys.stderr)
    sys.exit(1)


# ---------------------------------------------------------------------------
# Konstanta & Pola Regex
# ---------------------------------------------------------------------------

# Pola deteksi heading BAB: "BAB I", "BAB IV", "BAB XXIII", dst.
REGEX_BAB = re.compile(r'^BAB\s+[IVXLCDM]+$', re.IGNORECASE)

# Pola deteksi Sub-Bab: "1.1 Latar Belakang", "2.1", "3.2.1 Tujuan", dst.
REGEX_SUB_BAB = re.compile(r'^(\d+\.\d+(?:\.\d+)*)\s*(.*)?$')

# Style heading MS Word yang umum
HEADING_STYLES = {
    'heading 1', 'heading 2', 'heading 3',
    'judul bab', 'bab', 'headingbab',
    'heading1', 'heading2', 'heading3',
}


def log(msg: str):
    """Log ke stderr agar tidak mencemari stdout JSON output."""
    print(f"[parse_reference_doc] {msg}", file=sys.stderr)


def is_bab_paragraph(para) -> bool:
    """
    Deteksi apakah paragraf adalah heading BAB.
    Dua kriteria:
    1. Style paragraf termasuk Heading style (case-insensitive)
    2. Teks paragraf match regex ^BAB\\s+[IVXLCDM]+$
    """
    style_name = (para.style.name or '').lower().replace(' ', '').replace('-', '')
    text = para.text.strip()

    # Kriteria 1: berdasarkan style heading Word
    if any(style_name.startswith(h.replace(' ', '')) for h in HEADING_STYLES):
        if text:  # jangan proses heading kosong
            return True

    # Kriteria 2: berdasarkan teks match regex BAB
    if REGEX_BAB.match(text):
        return True

    return False


def extract_bab_nomor(text: str) -> str:
    """
    Ekstrak nomor romawi dari teks BAB.
    "BAB I" → "I", "BAB IV" → "IV"
    """
    text = text.strip().upper()
    m = re.match(r'^BAB\s+([IVXLCDM]+)', text)
    if m:
        return m.group(1)
    return text.replace('BAB', '').strip()


def extract_bab_judul(text: str) -> str:
    """
    Ekstrak judul dari teks heading BAB.
    "BAB I PENDAHULUAN" → "PENDAHULUAN"
    Jika hanya "BAB I" maka return ""
    """
    text = text.strip()
    m = re.match(r'^BAB\s+[IVXLCDM]+\s*(.*)$', text, re.IGNORECASE)
    if m:
        judul = m.group(1).strip()
        return judul
    return ""


def is_sub_bab_paragraph(para) -> bool:
    """
    Deteksi apakah paragraf adalah Sub-Bab.
    Kriteria: teks match regex ^\\d+\\.\\d+
    dan bukan paragraf yang hanya angka saja (mis. nomor halaman).
    """
    text = para.text.strip()
    if not text:
        return False
    m = REGEX_SUB_BAB.match(text)
    if m:
        # Minimal ada kode sub-bab yang valid
        kode = m.group(1)
        parts = kode.split('.')
        # Semua bagian harus angka
        if all(p.isdigit() for p in parts):
            return True
    return False


def parse_sub_bab(text: str) -> dict:
    """
    Parse teks Sub-Bab menjadi kode dan judul.
    "1.1 Latar Belakang" → {"kode": "1.1", "judul": "Latar Belakang"}
    """
    m = REGEX_SUB_BAB.match(text.strip())
    if m:
        kode = m.group(1)
        judul = (m.group(2) or '').strip()
        # Bersihkan leading/trailing noise
        judul = re.sub(r'^[.\s]+', '', judul).strip()
        return {"kode": kode, "judul": judul}
    return {"kode": "", "judul": text.strip()}


def get_cell_text(cell) -> str:
    """Ambil teks dari cell, gabungkan semua paragraf."""
    return ' '.join(p.text.strip() for p in cell.paragraphs if p.text.strip())


def has_merged_cells(table) -> bool:
    """
    Deteksi apakah tabel memiliki merged cells (rowspan/colspan).
    Cek via XML: gridSpan > 1 atau vMerge attribute.
    """
    tbl_xml = table._tbl
    # Cek colspan (gridSpan)
    for tc in tbl_xml.iter(qn('w:tc')):
        grid_span = tc.find(qn('w:tcPr'))
        if grid_span is not None:
            gs = grid_span.find(qn('w:gridSpan'))
            if gs is not None:
                val = gs.get(qn('w:val'))
                if val and int(val) > 1:
                    return True
    # Cek rowspan (vMerge)
    for vm in tbl_xml.iter(qn('w:vMerge')):
        val = vm.get(qn('w:val'))
        if val == 'restart' or val is None:
            return True
    return False


def get_table_header(table) -> list:
    """
    Ambil header tabel dari baris pertama.
    Gabungkan cell yang di-merge.
    Return list of string.
    """
    if not table.rows:
        return []
    header_row = table.rows[0]
    headers = []
    for cell in header_row.cells:
        text = get_cell_text(cell)
        if text not in headers:  # skip duplikat akibat merge
            headers.append(text)
    return headers


def get_actual_column_count(table) -> int:
    """
    Hitung jumlah kolom aktual (dari definisi grid, bukan baris pertama saja).
    """
    # Ambil dari gridCol elements di XML
    tbl_grid = table._tbl.find(qn('w:tblGrid'))
    if tbl_grid is not None:
        cols = tbl_grid.findall(qn('w:gridCol'))
        if cols:
            return len(cols)
    # Fallback: ambil dari baris dengan sel terbanyak
    max_cols = 0
    for row in table.rows:
        max_cols = max(max_cols, len(row.cells))
    return max_cols


def parse_document(docx_path: str) -> dict:
    """
    Fungsi utama: parsing dokumen .docx dan return struktur BAB/Sub-Bab/Tabel.

    Returns:
        dict dengan keys: "bab" (list) dan "tabel_terdeteksi" (list)

    Raises:
        ValueError: jika file tidak valid atau struktur tidak terdeteksi
    """
    log(f"Mulai parsing: {docx_path}")

    # --- Validasi file ---
    if not os.path.exists(docx_path):
        raise ValueError(f"File tidak ditemukan: {docx_path}")

    ext = os.path.splitext(docx_path)[1].lower()
    if ext != '.docx':
        raise ValueError(f"File bukan .docx (terdeteksi: '{ext}'). Hanya format .docx yang didukung.")

    # --- Buka dokumen ---
    try:
        doc = docx.Document(docx_path)
    except Exception as e:
        raise ValueError(f"File .docx corrupt atau tidak dapat dibuka: {e}")

    log(f"Dokumen berhasil dibuka. Jumlah paragraf: {len(doc.paragraphs)}, Jumlah tabel: {len(doc.tables)}")

    # --- Mapping posisi tabel: cari paragraf terakhir sebelum setiap tabel ---
    # Kita gunakan urutan elemen di body XML untuk menentukan posisi relatif
    body = doc.element.body
    body_children = list(body)

    # Buat map: tabel index → bab_code (bab terakhir sebelum tabel tersebut)
    tabel_bab_map = {}  # {table_index: bab_code}
    current_bab_before_table = None

    table_elements = list(doc.tables)
    table_index_map = {}  # xml element → index in doc.tables
    for i, tbl in enumerate(table_elements):
        table_index_map[tbl._tbl] = i

    for elem in body_children:
        tag = elem.tag.split('}')[-1] if '}' in elem.tag else elem.tag

        if tag == 'p':
            # Ambil teks paragraf dari element XML
            para_text = ''.join(
                t.text or '' for t in elem.iter('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}t')
            ).strip()
            if REGEX_BAB.match(para_text):
                nomor = extract_bab_nomor(para_text)
                current_bab_before_table = f"BAB {nomor}"
            elif para_text:
                # Cek style dari paragraf XML
                pPr = elem.find('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}pPr')
                if pPr is not None:
                    pStyle = pPr.find('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}pStyle')
                    if pStyle is not None:
                        style_val = (pStyle.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}val') or '').lower()
                        if any(h.replace(' ', '') in style_val for h in ['heading1', 'heading2', 'judul']):
                            bab_m = REGEX_BAB.match(para_text)
                            if bab_m:
                                nomor = extract_bab_nomor(para_text)
                                current_bab_before_table = f"BAB {nomor}"

        elif tag == 'tbl':
            tbl_idx = table_index_map.get(elem)
            if tbl_idx is not None:
                tabel_bab_map[tbl_idx] = current_bab_before_table or "TIDAK_DIKETAHUI"

    # --- Parsing BAB dan Sub-Bab dari paragraf ---
    bab_list = []
    current_bab = None
    current_bab_judul_found = False  # apakah judul BAB sudah ditemukan di baris berikutnya

    paragraphs = doc.paragraphs

    i = 0
    while i < len(paragraphs):
        para = paragraphs[i]
        text = para.text.strip()

        if not text:
            i += 1
            continue

        if is_bab_paragraph(para):
            nomor = extract_bab_nomor(text)
            judul = extract_bab_judul(text)

            # Jika judul kosong (hanya "BAB I"), cek paragraf berikutnya
            if not judul and i + 1 < len(paragraphs):
                next_text = paragraphs[i + 1].text.strip()
                # Jika paragraf berikutnya bukan sub-bab dan bukan BAB lain
                if next_text and not REGEX_BAB.match(next_text) and not REGEX_SUB_BAB.match(next_text):
                    judul = next_text
                    i += 1  # skip paragraf judul

            bab_code = f"BAB {nomor}"
            current_bab = {
                "nomor": nomor,
                "judul": judul.upper() if judul else "",
                "bab_code": bab_code,
                "sub_bab": []
            }
            bab_list.append(current_bab)
            log(f"  → BAB terdeteksi: {bab_code} '{judul}'")

        elif is_sub_bab_paragraph(para) and current_bab is not None:
            parsed = parse_sub_bab(text)
            kode = parsed["kode"]
            judul_sub = parsed["judul"]

            # Tipe konten default: rich_text
            # Akan di-override ke "tabel" jika ada tabel tepat setelahnya (logic di PHP)
            sub_bab_entry = {
                "kode": kode,
                "judul": judul_sub,
                "tipe_konten": "rich_text"
            }
            current_bab["sub_bab"].append(sub_bab_entry)
            log(f"    → Sub-Bab: {kode} '{judul_sub}'")

        i += 1

    # --- Parsing Tabel ---
    tabel_list = []
    for tbl_idx, table in enumerate(doc.tables):
        if not table.rows:
            log(f"  → Tabel #{tbl_idx} dilewati (kosong)")
            continue

        header = get_table_header(table)
        jumlah_kolom = get_actual_column_count(table)
        merged = has_merged_cells(table)
        lokasi_bab = tabel_bab_map.get(tbl_idx, "TIDAK_DIKETAHUI")

        # Filter: skip tabel yang sangat kecil (1 kolom) — biasanya tabel layout/ornamen
        if jumlah_kolom < 2:
            log(f"  → Tabel #{tbl_idx} dilewati (hanya {jumlah_kolom} kolom, kemungkinan layout)")
            continue

        tabel_entry = {
            "lokasi_bab": lokasi_bab,
            "jumlah_kolom": jumlah_kolom,
            "header": header,
            "has_rowspan_colspan": merged
        }
        tabel_list.append(tabel_entry)
        log(f"  → Tabel #{tbl_idx}: {jumlah_kolom} kolom, lokasi: {lokasi_bab}, header: {header[:3]}")

    # --- Validasi: minimal 1 BAB harus terdeteksi ---
    if not bab_list:
        raise ValueError(
            "Struktur BAB tidak terdeteksi sama sekali dalam dokumen ini. "
            "Pastikan dokumen menggunakan style Heading atau pola 'BAB [Romawi]'. "
            "Isi manual via editor jika diperlukan."
        )

    log(f"Parsing selesai: {len(bab_list)} BAB, {len(tabel_list)} tabel terdeteksi.")

    return {
        "bab": bab_list,
        "tabel_terdeteksi": tabel_list
    }


def main():
    if len(sys.argv) < 2:
        print(json.dumps({
            "error": True,
            "message": "Usage: python parse_reference_doc.py /path/to/file.docx"
        }), file=sys.stderr)
        sys.exit(1)

    docx_path = sys.argv[1]
    log(f"Input file: {docx_path}")

    try:
        result = parse_document(docx_path)
        # Output JSON ke stdout
        print(json.dumps(result, ensure_ascii=False, indent=2))
        sys.exit(0)

    except ValueError as e:
        # Error yang diketahui: file tidak valid, struktur tidak ditemukan, dll.
        error_response = {
            "error": True,
            "message": str(e),
            "bab": [],
            "tabel_terdeteksi": []
        }
        print(json.dumps(error_response, ensure_ascii=False), file=sys.stderr)
        sys.exit(1)

    except Exception as e:
        # Error tidak terduga
        error_response = {
            "error": True,
            "message": f"Error tidak terduga saat parsing: {type(e).__name__}: {e}",
            "bab": [],
            "tabel_terdeteksi": []
        }
        print(json.dumps(error_response, ensure_ascii=False), file=sys.stderr)
        sys.exit(2)


if __name__ == '__main__':
    main()
