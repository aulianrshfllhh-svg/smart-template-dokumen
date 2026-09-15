import sys
import os
import re
import site

# ---------------------------------------------------------------------------
# Dynamically resolve user site-packages path for Windows & web server processes
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

import docx
from docx.shared import Mm, Pt, Emu
from docx.enum.section import WD_ORIENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import nsdecls, qn
from copy import deepcopy
from lxml import etree


# ============================================================================
# KONSTANTA F4 (FOLIO / LEGAL INDONESIA)
# ============================================================================
F4_PORTRAIT_W = Mm(215)    # Lebar F4 Portrait (21.5 cm)
F4_PORTRAIT_H = Mm(350)    # Tinggi F4 Portrait (35.0 cm)
F4_LANDSCAPE_W = Mm(350)   # Lebar F4 Landscape (35.0 cm)
F4_LANDSCAPE_H = Mm(215)   # Tinggi F4 Landscape (21.5 cm)
MARGIN_20MM = Mm(20)       # Margin seragam 20mm semua sisi


# ============================================================================
# UTILITAS: Pembersih UTF-8
# ============================================================================
def clean_text_utf8(text):
    """
    Pembersih karakter UTF-8 universal:
    Menghapus simbol Unicode Replacement Character (\\ufffd),
    mengubah NBSP menjadi spasi normal, normalisasi smart quotes & dashes.
    """
    if not text:
        return ""
    text = text.replace('\ufffd', '')
    text = text.replace('\u00a0', ' ').replace('\xa0', ' ')
    text = text.replace('\u200b', '').replace('\ufeff', '').replace('\u00ad', '')
    text = text.replace('\u201c', '"').replace('\u201d', '"')
    text = text.replace('\u2018', "'").replace('\u2019', "'")
    text = text.replace('\u2013', '-').replace('\u2014', '-')
    return text


# ============================================================================
# 1. INJEKSI HEADER BLOCK RESMI PERBUP CIREBON
# ============================================================================
def inject_official_header_block(doc, romawi_header, opd_name, tahun):
    """
    Injeksi Header Block Resmi Perbup Cirebon (5 baris), posisi kanan halaman 1.
    Left-aligned text dengan Left Indent 95mm (ditempatkan di separuh kanan kertas).
    """
    romawi_clean = romawi_header.upper().replace('_', ' ').strip()
    if not romawi_clean.startswith('LAMPIRAN'):
        romawi_clean = f"LAMPIRAN {romawi_clean}"

    opd_line = f"RENCANA KERJA PERANGKAT DAERAH TAHUN {tahun}"

    header_lines = [
        romawi_clean,
        "PERATURAN BUPATI CIREBON",
        f"NOMOR TAHUN {tahun}",
        "TENTANG",
        opd_line
    ]

    # Step 1: Hapus paragraf header lama jika ada di 10 paragraf awal
    for p in list(doc.paragraphs[:10]):
        txt = p.text.upper().strip()
        if any(txt.startswith(kw) for kw in ["LAMPIRAN", "PERATURAN BUPATI", "NOMOR", "TENTANG", "RENCANA"]):
            try:
                p._element.getparent().remove(p._element)
            except Exception:
                pass

    # Step 2: Injeksi 5 baris header baru (Left Aligned + Left Indent 0mm)
    if len(doc.paragraphs) > 0:
        first_p = doc.paragraphs[0]
        for line in header_lines:
            p = first_p.insert_paragraph_before(line)
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT
            p.paragraph_format.left_indent = Mm(0)
            p.paragraph_format.space_before = Pt(0)
            p.paragraph_format.space_after = Pt(0)
            p.paragraph_format.line_spacing = 1.15
            for run in p.runs:
                run.bold = False
                run.font.name = 'Bookman Old Style'
                run.font.size = Pt(12)

        # Spasi pemisah setelah header block
        spacer = first_p.insert_paragraph_before("")
        spacer.paragraph_format.space_before = Pt(0)
        spacer.paragraph_format.space_after = Pt(12)
    else:
        for line in header_lines:
            p = doc.add_paragraph(line)
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT
            p.paragraph_format.left_indent = Mm(0)
            p.paragraph_format.space_before = Pt(0)
            p.paragraph_format.space_after = Pt(0)
            p.paragraph_format.line_spacing = 1.15
        doc.add_paragraph("")


# ============================================================================
# 1B. PAGE BREAK & STRUCTURE CONTROL (AWAL BAB)
# ============================================================================
from docx.enum.text import WD_BREAK

def enforce_bab_page_breaks(doc):
    """
    ATURAN PEMISAHAN HALAMAN OTOMATIS (PAGE BREAK PER BAB):
    1. Deteksi Header BAB utama (BAB I s/d BAB V).
    2. Sisipkan page_break_before = True & keep_with_next = True pada judul BAB.
    3. Hapus penumpukan paragraf/garis kosong sebelum & sesudah judul BAB.
    4. Pastikan judul BAB baru dimulai dari margin atas dengan perataan Tengah (Centered).
    """
    valid_babs = ['BAB I', 'BAB II', 'BAB III', 'BAB IV', 'BAB V']
    
    # Clean redundant consecutive page breaks & blank paragraphs first
    paragraphs_to_remove = []
    
    for i, p in enumerate(doc.paragraphs):
        raw_text = p.text.strip()
        text_clean = re.sub(r'\s+', ' ', raw_text.upper())
        
        is_bab_utama = False
        matched_bab = None
        for bab in valid_babs:
            if text_clean.startswith(bab):
                is_bab_utama = True
                matched_bab = bab
                break

        # FILTER KETAT: Identifikasi judul BAB utama (bukan konten sub-bab)
        if is_bab_utama:
            # Syarat 1: Teks heading BAB max ~70 karakter
            if len(raw_text) > 70:
                is_bab_utama = False
            
            # Syarat 2: Paragraf tidak boleh memiliki left indent besar
            try:
                indent = p.paragraph_format.left_indent
                if indent is not None and indent > Mm(10):
                    is_bab_utama = False
            except Exception:
                pass

            # Syarat 3: Teks tidak boleh mengandung penomoran sub-bab seperti "1.1", "2.1"
            if re.search(r'\d+\.\d+', raw_text):
                is_bab_utama = False
                
        if is_bab_utama:
            # 1. Bersihkan paragraf/garis kosong SEBELUM judul BAB ini (Prevent Empty Pages)
            prev_idx = i - 1
            while prev_idx >= 0:
                prev_p = doc.paragraphs[prev_idx]
                if not prev_p.text.strip():
                    paragraphs_to_remove.append(prev_p)
                    prev_idx -= 1
                else:
                    break
            
            # 2. Atur Properti Paragraf Judul BAB (Centered, keep_with_next, page_break_before)
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER
            p.paragraph_format.keep_with_next = True
            p.paragraph_format.space_before = Pt(0)
            p.paragraph_format.space_after = Pt(6)

            # Kecualikan BAB I jika berada di awal dokumen (berdekatan dengan header)
            if 'BAB I' in text_clean and i < 15:
                p.paragraph_format.page_break_before = False
            else:
                p.paragraph_format.page_break_before = True
            
            # 3. Bersihkan paragraf/garis kosong SETELAH Judul BAB
            next_idx = i + 1
            while next_idx < len(doc.paragraphs):
                next_p = doc.paragraphs[next_idx]
                if not next_p.text.strip():
                    paragraphs_to_remove.append(next_p)
                    next_idx += 1
                else:
                    break
        else:
            # Bukan judul BAB utama: pastikan teks mengalir alami
            p.paragraph_format.page_break_before = False

            # Hapus properti page break bawaan jika ada di level XML
            pPr = p._element.find(qn('w:pPr'))
            if pPr is not None:
                for pb in pPr.findall(qn('w:pageBreakBefore')):
                    pPr.remove(pb)
                for ol in pPr.findall(qn('w:outlineLvl')):
                    pPr.remove(ol)

    # Hapus semua paragraf kosong berlebih yang terkumpul
    for empty_p in set(paragraphs_to_remove):
        try:
            empty_p._element.getparent().remove(empty_p._element)
        except Exception:
            pass


# ============================================================================
# 2. TABEL: REPEAT HEADER + AUTOFIT + SPACING
# ============================================================================
def set_repeat_table_header(row):
    """Terapkan w:tblHeader pada row agar judul kolom berulang di halaman baru."""
    try:
        trPr = row._tr.get_or_add_trPr()
        existing = trPr.find(qn('w:tblHeader'))
        if existing is None:
            trPr.append(parse_xml(r'<w:tblHeader %s/>' % nsdecls('w')))
    except Exception:
        pass


def format_table_extreme(table):
    """
    PERBAIKAN EKSTREM TABEL:
    1. w:tblW = 5000 pct (100% lebar halaman).
    2. Hapus lebar kolom statis (w:tcW) agar kolom fleksibel.
    3. Repeat Header Rows (w:tblHeader) di row 0.
    4. Zero Bold, Bookman 12pt, Single Spacing (Space 0pt) pada semua sel.
    """
    # --- 1. Set lebar tabel 100% ---
    table.autofit = False
    try:
        tblPr = table._tbl.tblPr
        if tblPr is None:
            tblPr = OxmlElement('w:tblPr')
            table._tbl.insert(0, tblPr)

        # Hapus tblW lama
        for old_w in tblPr.findall(qn('w:tblW')):
            tblPr.remove(old_w)
        tblPr.append(parse_xml(r'<w:tblW %s w:w="5000" w:type="pct"/>' % nsdecls('w')))

        # Hapus autofit layout agar fixed width
        for old_layout in tblPr.findall(qn('w:tblLayout')):
            tblPr.remove(old_layout)
        tblPr.append(parse_xml(r'<w:tblLayout %s w:type="fixed"/>' % nsdecls('w')))
    except Exception:
        pass

    # --- 2. Repeat Header Rows ---
    if len(table.rows) > 0:
        set_repeat_table_header(table.rows[0])

    # --- 3. Bersihkan semua sel ---
    for row in table.rows:
        for cell in row.cells:
            # Jangan hapus w:tcW jika menggunakan w:tblLayout fixed agar tabel tidak memiliki kolom 0px
            tcPr = cell._tc.get_or_add_tcPr()

            for paragraph in cell.paragraphs:
                paragraph.paragraph_format.space_before = Pt(0)
                paragraph.paragraph_format.space_after = Pt(0)
                paragraph.paragraph_format.line_spacing = 1.0

                for run in paragraph.runs:
                    run.bold = False
                    run.font.name = 'Bookman Old Style'
                    run.font.size = Pt(8)
                    if run.text:
                        run.text = clean_text_utf8(run.text)

                # Zero bold di level rPr paragraf juga (menangani inherited bold)
                pPr = paragraph._element.find(qn('w:pPr'))
                if pPr is not None:
                    rPr = pPr.find(qn('w:rPr'))
                    if rPr is not None:
                        for b_elem in rPr.findall(qn('w:b')):
                            rPr.remove(b_elem)
                        for bCs_elem in rPr.findall(qn('w:bCs')):
                            rPr.remove(bCs_elem)


# ============================================================================
# UTILITIES & XML BREAK CONSTRUCTORS
# ============================================================================
def _to_twips(emu_or_mm_val):
    return int(emu_or_mm_val / 635) if isinstance(emu_or_mm_val, (int, float)) else int(emu_or_mm_val)

def set_landscape_section(section):
    """Set section properties to LANDSCAPE F4 (Width 33.0cm x Height 21.5cm, Margins: 2 cm / 20mm semua sisi)."""
    section.orientation = WD_ORIENT.LANDSCAPE
    section.page_width = F4_LANDSCAPE_W
    section.page_height = F4_LANDSCAPE_H
    section.top_margin = MARGIN_20MM
    section.bottom_margin = MARGIN_20MM
    section.left_margin = MARGIN_20MM
    section.right_margin = MARGIN_20MM

def set_portrait_section(section):
    """Set section properties to PORTRAIT F4 (Width 21.5cm x Height 33.0cm, Margins: 2 cm / 20mm semua sisi)."""
    section.orientation = WD_ORIENT.PORTRAIT
    section.page_width = F4_PORTRAIT_W
    section.page_height = F4_PORTRAIT_H
    section.top_margin = MARGIN_20MM
    section.bottom_margin = MARGIN_20MM
    section.left_margin = MARGIN_20MM
    section.right_margin = MARGIN_20MM

def _make_section_break_xml(orientation='portrait'):
    """Buat XML elemen w:sectPr untuk section break Next Page dengan margin 2 cm (20mm = 1134 twips) semua sisi."""
    if orientation == 'landscape':
        return (
            '<w:sectPr %s>'
            '<w:pgSz w:w="%d" w:h="%d" w:orient="landscape"/>'
            '<w:pgMar w:top="%d" w:right="%d" w:bottom="%d" w:left="%d" '
            'w:header="709" w:footer="709" w:gutter="0"/>'
            '<w:type w:val="nextPage"/>'
            '</w:sectPr>' % (
                nsdecls('w'),
                _to_twips(F4_LANDSCAPE_W), _to_twips(F4_LANDSCAPE_H),
                _to_twips(MARGIN_20MM), _to_twips(MARGIN_20MM), _to_twips(MARGIN_20MM), _to_twips(MARGIN_20MM),
            )
        )
    else:
        return (
            '<w:sectPr %s>'
            '<w:pgSz w:w="%d" w:h="%d" w:orient="portrait"/>'
            '<w:pgMar w:top="%d" w:right="%d" w:bottom="%d" w:left="%d" '
            'w:header="709" w:footer="709" w:gutter="0"/>'
            '<w:type w:val="nextPage"/>'
            '</w:sectPr>' % (
                nsdecls('w'),
                _to_twips(F4_PORTRAIT_W), _to_twips(F4_PORTRAIT_H),
                _to_twips(MARGIN_20MM), _to_twips(MARGIN_20MM), _to_twips(MARGIN_20MM), _to_twips(MARGIN_20MM),
            )
        )

# ============================================================================
# 1. ATURAN PEMISAHAN HALAMAN (PAGE CONTROL & BREAKS)
# ============================================================================
def add_page_break_before_chapters(doc):
    """
    1. Awal BAB (BAB I - BAB V): WAJIB dimulai dari awal halaman baru (page_break_before = True),
       kecuali jika di awal dokumen.
    2. Pembersihan Noise: Hapus paragraf kosong berturut-turut & kurangi halaman kosong beruntun.
    """
    body = doc._body._element

    # 1. Bersihkan paragraf kosong berturut-turut (multiple empty lines)
    paragraphs = list(doc.paragraphs)
    empty_count = 0
    for p in paragraphs:
        text = p.text.strip()
        has_drawing = any(elem.tag.endswith(('drawing', 'shape', 'pict')) for elem in p._element.iter())
        if not text and not has_drawing:
            empty_count += 1
            if empty_count > 1:
                try:
                    p._element.getparent().remove(p._element)
                except Exception:
                    pass
        else:
            empty_count = 0

    # 2. Terapkan Page Break pada Judul BAB
    valid_babs = ['BAB I', 'BAB II', 'BAB III', 'BAB IV', 'BAB V']
    paragraphs = list(doc.paragraphs)
    
    for i, p in enumerate(paragraphs):
        raw_text = p.text.strip()
        text_clean = re.sub(r'\s+', ' ', raw_text.upper())
        
        is_bab_utama = any(text_clean.startswith(bab) for bab in valid_babs)

        if is_bab_utama:
            if len(raw_text) > 70:
                is_bab_utama = False
            try:
                indent = p.paragraph_format.left_indent
                if indent is not None and indent > Mm(10):
                    is_bab_utama = False
            except Exception:
                pass
            if re.search(r'\d+\.\d+', raw_text):
                is_bab_utama = False

        if is_bab_utama:
            # Hapus paragraf kosong persis sebelum BAB
            prev_idx = i - 1
            while prev_idx >= 0:
                prev_p = paragraphs[prev_idx]
                if not prev_p.text.strip():
                    try:
                        prev_p._element.getparent().remove(prev_p._element)
                    except Exception:
                        pass
                    prev_idx -= 1
                else:
                    break

            # Kecualikan jika di awal dokumen
            if 'BAB I' in text_clean and i < 15:
                p.paragraph_format.page_break_before = False
            else:
                p.paragraph_format.page_break_before = True

            # Hapus paragraf kosong persis setelah BAB
            next_idx = i + 1
            while next_idx < len(paragraphs):
                next_p = paragraphs[next_idx]
                if not next_p.text.strip():
                    try:
                        next_p._element.getparent().remove(next_p._element)
                    except Exception:
                        pass
                    next_idx += 1
                else:
                    break
        else:
            p.paragraph_format.page_break_before = False
            pPr = p._element.find(qn('w:pPr'))
            if pPr is not None:
                for pb in pPr.findall(qn('w:pageBreakBefore')):
                    pPr.remove(pb)

def enforce_bab_page_breaks(doc):
    """Fungsi alias / wrapper untuk add_page_break_before_chapters(doc)."""
    add_page_break_before_chapters(doc)

# ============================================================================
# 3. FITUR KELENGKAPAN TABEL MATRIKS (TABLE HEADERS & SPLIT CONTROL)
# ============================================================================
def format_table_headers_and_splits(table):
    """
    - Pengulangan Header Tabel (tblHeader) pada baris pertama.
    - Pencegahan Pemotongan Baris (cantSplit) di seluruh baris tabel.
    """
    for i, row in enumerate(table.rows):
        trPr = row._tr.get_or_add_trPr()
        
        # cantSplit pada semua baris
        existing_cant_split = trPr.find(qn('w:cantSplit'))
        if existing_cant_split is None:
            trPr.append(parse_xml(r'<w:cantSplit %s/>' % nsdecls('w')))

        # tblHeader hanya pada baris pertama
        if i == 0:
            existing_tbl_header = trPr.find(qn('w:tblHeader'))
            if existing_tbl_header is None:
                trPr.append(parse_xml(r'<w:tblHeader %s/>' % nsdecls('w')))

def format_table_extreme(table):
    """Format tabel: 100% width, repeat header, cantSplit, reset font/spacing."""
    table.autofit = False
    try:
        tblPr = table._tbl.tblPr
        if tblPr is None:
            tblPr = OxmlElement('w:tblPr')
            table._tbl.insert(0, tblPr)

        for old_w in tblPr.findall(qn('w:tblW')):
            tblPr.remove(old_w)
        tblPr.append(parse_xml(r'<w:tblW %s w:w="5000" w:type="pct"/>' % nsdecls('w')))

        for old_layout in tblPr.findall(qn('w:tblLayout')):
            tblPr.remove(old_layout)
        tblPr.append(parse_xml(r'<w:tblLayout %s w:type="fixed"/>' % nsdecls('w')))
    except Exception:
        pass

    # Header repeat & cantSplit
    format_table_headers_and_splits(table)

    # Format sel
    for row in table.rows:
        for cell in row.cells:
            for paragraph in cell.paragraphs:
                paragraph.paragraph_format.space_before = Pt(0)
                paragraph.paragraph_format.space_after = Pt(0)
                paragraph.paragraph_format.line_spacing = 1.0

                for run in paragraph.runs:
                    run.bold = False
                    run.font.name = 'Bookman Old Style'
                    run.font.size = Pt(8)
                    if run.text:
                        run.text = clean_text_utf8(run.text)

                pPr = paragraph._element.find(qn('w:pPr'))
                if pPr is not None:
                    rPr = pPr.find(qn('w:rPr'))
                    if rPr is not None:
                        for b_elem in rPr.findall(qn('w:b')):
                            rPr.remove(b_elem)
                        for bCs_elem in rPr.findall(qn('w:bCs')):
                            rPr.remove(bCs_elem)

# ============================================================================
# 2. LAYOUT-AWARE AUTO FIX: TABLE LAYOUT & PAGE ORIENTATION ENGINE
# ============================================================================

class TableLayoutAnalyzer:
    """
    Melakukan analisis mendalam terhadap lebar dan struktur tabel sebelum formatting:
    - jumlah kolom & baris
    - panjang teks header & kata terpanjang setiap kolom
    - estimasi minimum width setiap kolom
    - total minimum width tabel
    - available page width (Portrait vs Landscape)
    - page orientation saat ini & margin
    """

    HIGH_PRIORITY_KEYWORDS = [
        'URAIAN', 'INDIKATOR', 'SASARAN', 'PROGRAM', 'KEGIATAN', 'SUB KEGIATAN',
        'KETERANGAN', 'LOKASI', 'SUMBER DANA', 'TARGET', 'REALISASI', 'FORMULASI'
    ]
    LOW_PRIORITY_KEYWORDS = [
        'NO', 'NOMOR', 'KODE', 'TAHUN', 'SATUAN', '%', 'PERSEN', 'AK', 'KD'
    ]

    def __init__(self, default_portrait_w_cm=17.5, default_landscape_w_cm=31.0):
        self.default_portrait_w_cm = default_portrait_w_cm
        self.default_landscape_w_cm = default_landscape_w_cm

    def analyze(self, table, current_orientation='portrait', page_width_cm=21.5, left_margin_cm=2.0, right_margin_cm=2.0, page_height_cm=33.0):
        num_rows = len(table.rows)
        if num_rows == 0:
            return None

        first_row = table.rows[0]
        num_cols = len(first_row.cells)
        if num_cols == 0:
            return None

        headers = []
        col_max_chars = [0] * num_cols
        col_max_word_len = [0] * num_cols

        for c_idx in range(num_cols):
            cell_txt = clean_text_utf8(first_row.cells[c_idx].text).strip() if c_idx < len(first_row.cells) else ""
            headers.append(cell_txt)
            col_max_chars[c_idx] = len(cell_txt)
            words = cell_txt.split()
            col_max_word_len[c_idx] = max([len(w) for w in words], default=0)

        # Sample up to 25 data rows for deeper analysis
        for r_idx in range(1, min(25, num_rows)):
            row = table.rows[r_idx]
            for c_idx in range(min(num_cols, len(row.cells))):
                cell_txt = clean_text_utf8(row.cells[c_idx].text).strip()
                if len(cell_txt) > col_max_chars[c_idx]:
                    col_max_chars[c_idx] = len(cell_txt)
                words = cell_txt.split()
                max_w = max([len(w) for w in words], default=0)
                if max_w > col_max_word_len[c_idx]:
                    col_max_word_len[c_idx] = max_w

        min_col_widths = []
        col_weights = []

        for c_idx in range(num_cols):
            header_txt = headers[c_idx].upper() if c_idx < len(headers) else ""
            is_low = any(kw in header_txt for kw in self.LOW_PRIORITY_KEYWORDS) or (col_max_chars[c_idx] <= 7 and not any(kw in header_txt for kw in self.HIGH_PRIORITY_KEYWORDS))
            is_high = any(kw in header_txt for kw in self.HIGH_PRIORITY_KEYWORDS) or col_max_chars[c_idx] > 30

            if is_low:
                min_w = max(0.9, min(1.6, col_max_word_len[c_idx] * 0.16 + 0.6))
                weight = 1.0
            elif is_high:
                min_w = max(3.5, min(7.5, col_max_word_len[c_idx] * 0.22 + 2.2))
                weight = 4.0 if ('URAIAN' in header_txt or 'INDIKATOR' in header_txt) else 3.0
            else:
                min_w = max(1.8, min(3.2, col_max_word_len[c_idx] * 0.18 + 1.1))
                weight = 2.0

            min_col_widths.append(round(min_w, 2))
            col_weights.append(weight)

        required_table_width = round(sum(min_col_widths) + (num_cols * 0.15), 2)
        available_portrait_width = round(page_width_cm - left_margin_cm - right_margin_cm, 2)
        available_landscape_width = round(page_height_cm - left_margin_cm - right_margin_cm, 2)

        return {
            'num_cols': num_cols,
            'num_rows': num_rows,
            'headers': headers,
            'col_max_chars': col_max_chars,
            'min_col_widths': min_col_widths,
            'col_weights': col_weights,
            'required_table_width': required_table_width,
            'available_portrait_width': available_portrait_width,
            'available_landscape_width': available_landscape_width,
            'current_orientation': current_orientation
        }


class OrientationDecisionEngine:
    """
    Implementasi logic keputusan orientasi halaman:
    IF required_table_width <= available_portrait_width -> PORTRAIT
    ELSE IF required_table_width <= available_landscape_width -> LANDSCAPE
    ELSE -> LANDSCAPE WITH ADVANCED FITTING

    SAFETY RULE:
    Jangan melakukan orientation change HANYA karena jumlah kolom.
    Gunakan kombinasi threshold (columns >= 5) + width analysis.
    """

    def decide(self, analysis):
        if not analysis:
            return {'decision': 'PORTRAIT', 'need_landscape': False, 'diagnostic': ''}

        num_cols = analysis['num_cols']
        req_w = analysis['required_table_width']
        avail_port = analysis['available_portrait_width']
        avail_land = analysis['available_landscape_width']

        headers_sample = ", ".join([h for h in analysis['headers'] if h][:4])
        if len(analysis['headers']) > 4:
            headers_sample += "..."

        if num_cols < 5 and req_w <= avail_port:
            portrait_suitable = "SUITABLE"
            landscape_suitable = "NOT NEEDED"
            decision = "PORTRAIT"
            action = "Keep Portrait. Perform normal table optimization."
        elif req_w <= avail_port:
            portrait_suitable = "SUITABLE"
            landscape_suitable = "NOT NEEDED"
            decision = "PORTRAIT"
            action = "Keep Portrait. Perform normal table optimization."
        elif req_w <= avail_land:
            portrait_suitable = "NOT SUITABLE"
            landscape_suitable = "SUITABLE"
            decision = "LANDSCAPE"
            action = "Change table section to Landscape. Recalculate table width & repeat header."
        else:
            portrait_suitable = "NOT SUITABLE"
            landscape_suitable = "SUITABLE WITH OPTIMIZATION"
            decision = "LANDSCAPE_WITH_ADVANCED_FITTING"
            action = "Change table section to Landscape. Apply advanced column width fitting & readable min font."

        diagnostic = (
            f"TABLE DETECTED\n"
            f"Headers: {headers_sample}\n"
            f"Columns: {num_cols} | Rows: {analysis['num_rows']}\n"
            f"Current Orientation: {analysis['current_orientation'].title()}\n"
            f"Required Width: {req_w} cm\n"
            f"Available Portrait Width: {avail_port} cm\n"
            f"Available Landscape Width: {avail_land} cm\n\n"
            f"DECISION:\n"
            f"Portrait = {portrait_suitable}\n"
            f"Landscape = {landscape_suitable}\n\n"
            f"ACTION:\n"
            f"{action}"
        )

        return {
            'decision': decision,
            'need_landscape': 'LANDSCAPE' in decision,
            'diagnostic': diagnostic,
            'analysis': analysis
        }


def _ensure_paragraph_before(body, elem):
    prev = elem.getprevious()
    if prev is not None and prev.tag.endswith('}p'):
        return prev
    new_p = OxmlElement('w:p')
    body.insert(list(body).index(elem), new_p)
    return new_p

def _ensure_paragraph_after(body, elem):
    nxt = elem.getnext()
    if nxt is not None and nxt.tag.endswith('}p'):
        return nxt
    new_p = OxmlElement('w:p')
    elem.addnext(new_p)
    return new_p

def _attach_sectPr_to_paragraph(p_elem, sect_xml):
    pPr = p_elem.find(qn('w:pPr'))
    if pPr is None:
        pPr = OxmlElement('w:pPr')
        p_elem.insert(0, pPr)
    existing = pPr.find(qn('w:sectPr'))
    if existing is not None:
        return
    sectPr = parse_xml(sect_xml)
    pPr.append(sectPr)


class SectionOrientationManager:
    """
    SECTION-AWARE ORIENTATION MANAGER:
    Penerapan orientasi khusus pada SECTION tempat tabel berada.
    Mencegah perubahan orientasi global pada seluruh dokumen.
    Mencegah penumpukan section break ganda.
    """

    def apply_table_section_orientations(self, doc, table_decisions):
        body = doc._body._element

        for item in table_decisions:
            tbl_elem = item['tbl_elem']
            dec = item['decision']

            if dec.get('need_landscape'):
                anchor_elem = tbl_elem
                cur = tbl_elem.getprevious()
                scan_count = 0
                while cur is not None and scan_count < 5:
                    if cur.tag.endswith('}p'):
                        p_text = ''.join(t.text or '' for t in cur.iter() if t.tag.endswith('}t')).strip().upper()
                        if p_text.startswith('TABEL') or p_text == '':
                            anchor_elem = cur
                            cur = cur.getprevious()
                            scan_count += 1
                        else:
                            break
                    else:
                        break

                p_before = _ensure_paragraph_before(body, anchor_elem)
                _attach_sectPr_to_paragraph(p_before, _make_section_break_xml('portrait'))

                p_after = _ensure_paragraph_after(body, tbl_elem)
                _attach_sectPr_to_paragraph(p_after, _make_section_break_xml('landscape'))

        enforce_all_xml_section_properties(doc)


class TableFitOptimizer:
    """
    Penyelarasan tabel pasca-orientasi:
    1. Recalculate available width
    2. Recalculate column widths berdasarkan prioritas konten
    3. Fit table to page width
    4. Repeat header (w:tblHeader) & row break control (w:cantSplit)
    5. Formatting font readable (min 8.5pt) & zero bold
    """

    def optimize(self, table, decision_info):
        analysis = decision_info.get('analysis')
        if not analysis:
            return

        need_landscape = decision_info.get('need_landscape', False)
        avail_width_cm = analysis['available_landscape_width'] if need_landscape else analysis['available_portrait_width']

        total_avail_twips = int(avail_width_cm * 567)
        num_cols = analysis['num_cols']
        col_weights = analysis['col_weights']
        min_col_widths = analysis['min_col_widths']
        headers = analysis['headers']

        sum_weights = sum(col_weights)
        col_twips = []
        for c_idx in range(num_cols):
            prop_twips = int((col_weights[c_idx] / max(1.0, sum_weights)) * total_avail_twips)
            min_twips = int(min_col_widths[c_idx] * 567)
            col_twips.append(max(prop_twips, min_twips))

        sum_twips = sum(col_twips)
        if sum_twips > 0:
            col_twips = [int((tw / sum_twips) * total_avail_twips) for tw in col_twips]

        table.autofit = False
        try:
            tblPr = table._tbl.tblPr
            if tblPr is None:
                tblPr = OxmlElement('w:tblPr')
                table._tbl.insert(0, tblPr)

            for old_w in tblPr.findall(qn('w:tblW')):
                tblPr.remove(old_w)
            tblPr.append(parse_xml(r'<w:tblW %s w:w="%d" w:type="dxa"/>' % (nsdecls('w'), total_avail_twips)))

            for old_layout in tblPr.findall(qn('w:tblLayout')):
                tblPr.remove(old_layout)
            tblPr.append(parse_xml(r'<w:tblLayout %s w:type="fixed"/>' % nsdecls('w')))
        except Exception:
            pass

        target_font_pt = 8.5 if num_cols >= 8 else 9.5

        for r_idx, row in enumerate(table.rows):
            is_header_row = (r_idx == 0)
            trPr = row._tr.get_or_add_trPr()

            # multi-page table handling: repeat header
            if is_header_row:
                if trPr.find(qn('w:tblHeader')) is None:
                    trPr.append(parse_xml(r'<w:tblHeader %s/>' % nsdecls('w')))

            for c_idx, cell in enumerate(row.cells):
                if c_idx < len(col_twips):
                    cell.width = Emu(col_twips[c_idx] * 635)
                    tcPr = cell._tc.get_or_add_tcPr()
                    for old_tcw in tcPr.findall(qn('w:tcW')):
                        tcPr.remove(old_tcw)
                    tcPr.append(parse_xml(r'<w:tcW %s w:w="%d" w:type="dxa"/>' % (nsdecls('w'), col_twips[c_idx])))

                    vAlign = tcPr.find(qn('w:vAlign'))
                    if vAlign is None:
                        vAlign = OxmlElement('w:vAlign')
                        tcPr.append(vAlign)
                    vAlign.set(qn('w:val'), 'center' if is_header_row else 'top')

                header_txt = headers[c_idx].upper() if c_idx < len(headers) else ""
                is_short_col = any(kw in header_txt for kw in ['NO', 'KODE', 'TAHUN', 'SATUAN', '%'])

                for p in cell.paragraphs:
                    p.paragraph_format.space_before = Pt(0)
                    p.paragraph_format.space_after = Pt(0)
                    p.paragraph_format.line_spacing = 1.0

                    if is_header_row or is_short_col:
                        p.alignment = WD_ALIGN_PARAGRAPH.CENTER
                    else:
                        p.alignment = WD_ALIGN_PARAGRAPH.LEFT

                    for run in p.runs:
                        run.bold = False
                        run.font.name = 'Bookman Old Style'
                        run.font.size = Pt(target_font_pt)
                        if run.text:
                            run.text = clean_text_utf8(run.text)

                    pPr = p._element.find(qn('w:pPr'))
                    if pPr is not None:
                        rPr = pPr.find(qn('w:rPr'))
                        if rPr is not None:
                            for b_elem in rPr.findall(qn('w:b')):
                                rPr.remove(b_elem)
                            for bCs_elem in rPr.findall(qn('w:bCs')):
                                rPr.remove(bCs_elem)


def isolate_wide_table_sections(doc):
    """
    Alias wrapper pendukung backwards compatibility yang memanggil Layout-Aware Auto Fix.
    """
    process_layout_aware_table_autofix(doc)


def process_layout_aware_table_autofix(doc):
    """
    Fungsi utama Layout-Aware Auto Fix:
    1. Table Layout Analysis
    2. Orientation Decision Logic
    3. Section Orientation Isolation (Landscape only for tables needing it)
    4. Table Fitting Post-Orientation
    5. Returns diagnostic log list
    """
    analyzer = TableLayoutAnalyzer()
    decision_engine = OrientationDecisionEngine()
    section_manager = SectionOrientationManager()
    optimizer = TableFitOptimizer()

    body = doc._body._element
    table_elements = [el for el in list(body) if el.tag.endswith('}tbl')]

    table_decisions = []
    diagnostics = []

    for idx, tbl_elem in enumerate(table_elements):
        table = docx.table.Table(tbl_elem, doc)
        analysis = analyzer.analyze(table)
        if not analysis:
            continue

        decision_info = decision_engine.decide(analysis)
        table_decisions.append({
            'tbl_elem': tbl_elem,
            'table': table,
            'decision': decision_info
        })

        if decision_info.get('diagnostic'):
            diagnostics.append(f"=== AUTO FIX DIAGNOSTIC (TABLE {idx + 1}) ===\n" + decision_info['diagnostic'])

    # Apply Section Orientation
    section_manager.apply_table_section_orientations(doc, table_decisions)

    # Optimize Table Fitting
    for item in table_decisions:
        optimizer.optimize(item['table'], item['decision'])

    # Print Diagnostic Logs
    if diagnostics:
        full_diag_text = "\n\n".join(diagnostics)
        print("AUTO_FIX_DIAGNOSTIC_START")
        print(full_diag_text)
        print("AUTO_FIX_DIAGNOSTIC_END")

    return diagnostics

# ============================================================================
# ZERO BOLD & FONT RESET GLOBAL (KETENTUAN DOKUMEN RENJA)
# ============================================================================
def clear_all_headers_and_footers(doc):
    """
    KETENTUAN 6: Header dan Footer KOSONG.
    Tidak ada header, footer, nomor halaman otomatis, maupun garis header/footer.
    """
    for section in doc.sections:
        section.header.is_linked_to_previous = False
        section.footer.is_linked_to_previous = False
        for p in section.header.paragraphs:
            p.text = ""
        for p in section.footer.paragraphs:
            p.text = ""

def strip_all_bold_xml_nodes(doc):
    """
    HAPUS SELURUH ATRIBUT BOLD (w:b & w:bCs) DAN PAKSA w:b w:val="false" DARI SELURUH STRUKTUR XML DOCX:
    1. Body XML (Seluruh paragraf, run, heading, tabel, cell, numbering level prefixes 1.1., 1.2., BAB I)
    2. Styles XML (Style bawaan Word seperti Heading 1, Heading 2, Table Header, dll)
    3. Header & Footer XML
    """
    # 1. Main Document Body XML Tree: Hapus semua b & bCs
    try:
        for b_tag in list(doc._element.xpath('//*[local-name()="b" or local-name()="bCs"]')):
            try:
                b_tag.getparent().remove(b_tag)
            except Exception:
                pass
    except Exception:
        pass

    # 2. Styles XML Tree (w:style elements)
    try:
        if hasattr(doc, 'styles') and hasattr(doc.styles, '_element'):
            for b_tag in list(doc.styles._element.xpath('//*[local-name()="b" or local-name()="bCs"]')):
                try:
                    b_tag.getparent().remove(b_tag)
                except Exception:
                    pass
    except Exception:
        pass

    # 3. Header & Footer XML Trees
    try:
        for section in doc.sections:
            for container in [section.header, section.footer, section.first_page_header, section.first_page_footer]:
                if container and hasattr(container, '_element'):
                    for b_tag in list(container._element.xpath('//*[local-name()="b" or local-name()="bCs"]')):
                        try:
                            b_tag.getparent().remove(b_tag)
                        except Exception:
                            pass
    except Exception:
        pass

    # 4. Paksa <w:b w:val="false"/> & <w:bCs w:val="false"/> pada SELURUH w:rPr agar style bawaan Word (Heading/List 1.1.) TIDAK mewariskan bold
    try:
        for rPr in doc._element.xpath('//*[local-name()="rPr"]'):
            for old_b in list(rPr.xpath('*[local-name()="b" or local-name()="bCs"]')):
                try:
                    rPr.remove(old_b)
                except Exception:
                    pass
            rPr.append(parse_xml(r'<w:b %s w:val="false"/>' % nsdecls('w')))
            rPr.append(parse_xml(r'<w:bCs %s w:val="false"/>' % nsdecls('w')))
    except Exception:
        pass

def enforce_all_xml_section_properties(doc):
    """
    Terapkan Page Setup F4 (215 mm x 330 mm) & Margin 2 cm (20mm = 1134 twips) 
    secara programatik pada SELURUH elemen w:sectPr di dokumen (Body, Paragraphs, Sections).
    """
    twip_w_portrait = _to_twips(F4_PORTRAIT_W)
    twip_h_portrait = _to_twips(F4_PORTRAIT_H)
    twip_w_landscape = _to_twips(F4_LANDSCAPE_W)
    twip_h_landscape = _to_twips(F4_LANDSCAPE_H)
    twip_margin = _to_twips(MARGIN_20MM)

    # 1. Update python-docx high-level section properties
    for section in doc.sections:
        if section.orientation == WD_ORIENT.LANDSCAPE:
            section.page_width = F4_LANDSCAPE_W
            section.page_height = F4_LANDSCAPE_H
        else:
            section.page_width = F4_PORTRAIT_W
            section.page_height = F4_PORTRAIT_H
        section.top_margin = MARGIN_20MM
        section.bottom_margin = MARGIN_20MM
        section.left_margin = MARGIN_20MM
        section.right_margin = MARGIN_20MM

    # 2. Update all low-level XML w:sectPr nodes
    try:
        for sectPr in doc._element.xpath('//*[local-name()="sectPr"]'):
            pgSz = sectPr.find(qn('w:pgSz'))
            if pgSz is None:
                pgSz = OxmlElement('w:pgSz')
                sectPr.append(pgSz)

            orient = pgSz.get(qn('w:orient'), 'portrait')
            if orient == 'landscape':
                pgSz.set(qn('w:w'), str(twip_w_landscape))
                pgSz.set(qn('w:h'), str(twip_h_landscape))
                pgSz.set(qn('w:orient'), 'landscape')
            else:
                pgSz.set(qn('w:w'), str(twip_w_portrait))
                pgSz.set(qn('w:h'), str(twip_h_portrait))
                pgSz.set(qn('w:orient'), 'portrait')

            pgMar = sectPr.find(qn('w:pgMar'))
            if pgMar is None:
                pgMar = OxmlElement('w:pgMar')
                sectPr.append(pgMar)

            pgMar.set(qn('w:top'), str(twip_margin))
            pgMar.set(qn('w:bottom'), str(twip_margin))
            pgMar.set(qn('w:left'), str(twip_margin))
            pgMar.set(qn('w:right'), str(twip_margin))
            pgMar.set(qn('w:header'), "709")
            pgMar.set(qn('w:footer'), "709")
            pgMar.set(qn('w:gutter'), "0")
    except Exception:
        pass

def apply_global_font_reset(doc):
    """
    KETENTUAN DOKUMEN RENJA:
    1. Ukuran Kertas: F4 / Folio (215mm x 330mm)
    2. Margin: 2 cm (20mm) di semua sisi (Atas, Bawah, Kiri, Kanan)
    3. Font: Bookman Old Style 12 pt (seluruh isi dokumen, judul BAB, sub-BAB, paragraf, tabel)
    4. Bold: TIDAK ADA HURUF BOLD (Zero Bold pada seluruh run, heading, tabel, style)
    5. Header & Footer: KOSONG (tanpa nomor halaman otomatis)
    """
    clear_all_headers_and_footers(doc)
    strip_all_bold_xml_nodes(doc)
    enforce_all_xml_section_properties(doc)

    for paragraph in doc.paragraphs:
        has_drawing = any(elem.tag.endswith(('drawing', 'shape', 'pict')) for elem in paragraph._element.iter())
        is_empty = not paragraph.text.strip()

        if not has_drawing and not is_empty:
            paragraph.paragraph_format.space_before = Pt(0)
            paragraph.paragraph_format.space_after = Pt(0)
            paragraph.paragraph_format.line_spacing = 1.15

        if is_empty and not has_drawing:
            continue

        for run in paragraph.runs:
            run.bold = False
            run.font.name = 'Bookman Old Style'
            run.font.size = Pt(12)
            if run.text:
                run.text = clean_text_utf8(run.text)

    for table in doc.tables:
        for row in table.rows:
            for cell in row.cells:
                for paragraph in cell.paragraphs:
                    paragraph.paragraph_format.space_before = Pt(0)
                    paragraph.paragraph_format.space_after = Pt(0)
                    paragraph.paragraph_format.line_spacing = 1.0

                    for run in paragraph.runs:
                        run.bold = False
                        run.font.name = 'Bookman Old Style'
                        run.font.size = Pt(12)

    try:
        for style in doc.styles:
            if hasattr(style, 'font') and style.font is not None:
                style.font.bold = False
                style.font.name = 'Bookman Old Style'
                style.font.size = Pt(12)
    except Exception:
        pass

    # Re-run XML XPath sweep after style modification to guarantee Zero Bold
    strip_all_bold_xml_nodes(doc)

# ============================================================================
# METADATA & FILENAME HELPERS
# ============================================================================
def parse_document_metadata(doc, default_romawi="LAMPIRAN LIII", default_opd="Kecamatan Losari", default_tahun="2027"):
    text_content = ""
    for p in doc.paragraphs[:25]:
        text_content += clean_text_utf8(p.text) + "\n"

    romawi_match = re.search(r'LAMPIRAN\s+([IVXLCDM]+)', text_content, re.IGNORECASE)
    romawi_val = f"LAMPIRAN_{romawi_match.group(1).upper()}" if romawi_match else default_romawi.replace(' ', '_').upper()

    tahun_match = (
        re.search(r'TAHUN\s+ANGGARAN\s+(\d{4})', text_content, re.IGNORECASE) or
        re.search(r'TAHUN\s+(\d{4})', text_content, re.IGNORECASE) or
        re.search(r'\b(202\d|203\d)\b', text_content)
    )
    tahun_val = tahun_match.group(1) if tahun_match else default_tahun

    opd_match = (
        re.search(r'RENCANA\s+KERJA\s+([A-Z0-9\s]+)', text_content, re.IGNORECASE) or
        re.search(r'PERANGKAT\s+DAERAH\s*:?\s*([A-Z0-9\s]+)', text_content, re.IGNORECASE)
    )
    if opd_match:
        raw_opd = opd_match.group(1).strip().split('\n')[0]
        raw_opd = re.sub(r'\s*TAHUN\s*\d{4}\s*$', '', raw_opd, flags=re.IGNORECASE).strip()
        opd_val = re.sub(r'[^a-zA-Z0-9 ]', '', raw_opd).strip()
        opd_val = re.sub(r'\s+', '_', opd_val)
    else:
        opd_val = default_opd.replace(' ', '_')

    opd_val = re.sub(r'_+', '_', opd_val).strip('_')
    return romawi_val, opd_val, tahun_val

def build_output_filename(romawi_str, opd_str, tahun_str="2027"):
    romawi_str = romawi_str.replace('_', ' ').strip()
    parts = romawi_str.split(' ')
    romawi_num = parts[-1].upper() if len(parts) >= 2 else romawi_str.upper()

    opd_clean = re.sub(r'_+', '_', opd_str.replace(' ', '_').strip('_'))
    filename = f"LAMPIRAN_{romawi_num}_Renja_{opd_clean}_TA{tahun_str}.docx"
    
    if romawi_str == "LAMPIRAN LIII" and opd_str == "Kecamatan_Losari":
        return None
    return filename

# ============================================================================
# STRIPPING FRONT MATTER & DAFTAR ISI KHUSUS TEMPLATE RENJA
# ============================================================================
def is_toc_or_leader_line(text):
    """
    Mengecek apakah paragraf merupakan baris Daftar Isi (TOC):
    Ciri TOC: mengandung titik-titik (.....), tab leader (…), 
    atau diakhiri nomor halaman (misal: "KATA PENGANTAR............i" atau "BAB I 1" atau "PENDAHULUAN ......... 1")
    """
    if not text:
        return False
    t = text.strip()
    if re.search(r'\.{3,}', t) or '…' in t or '. . .' in t:
        return True
    if re.search(r'\.\s*\.\s*\.', t):
        return True
    if re.search(r'\s+([0-9]+|[ivxlcdm]+)$', t, re.IGNORECASE) and not re.search(r'^\s*BAB\s+[IVXLCDM0-9]+', t, re.IGNORECASE):
        return True
    return False

def strip_renja_front_matter(doc):
    """
    Khusus Template Renja:
    Hapus TOTAL SELURUH Cover, Kata Pengantar, Daftar Isi (TOC fields, sdt, paragraf, tabel),
    Daftar Tabel, Daftar Gambar, dll. sebelum Judul ASLI BAB I.
    """
    body = doc._body._element

    # 1. Hapus seluruh Word TOC fields XML (<w:sdt> / <w:fldSimple> bertuliskan TOC)
    try:
        for sdt in list(body.xpath('//*[local-name()="sdt"]')):
            try:
                sdt.getparent().remove(sdt)
            except Exception:
                pass
    except Exception:
        pass

    # 2. Temukan elemen Judul ASLI BAB I (Bukan baris Daftar Isi)
    real_bab1_elem = None
    for p in doc.paragraphs:
        txt = clean_text_utf8(p.text).upper().strip()
        # Lewati jika ini baris Daftar Isi (berisi titik-titik atau nomor halaman)
        if is_toc_or_leader_line(txt):
            continue

        # Cek apakah judul asli BAB I
        if re.search(r'^\s*BAB\s+(I|1)\b', txt) or re.search(r'^\s*BAB\s+I\s*[-–:]?\s*PENDAHULUAN', txt):
            real_bab1_elem = p._element
            break

    if real_bab1_elem is not None:
        all_children = list(body)
        try:
            bab1_idx = all_children.index(real_bab1_elem)
            elements_to_remove = []

            for elem in all_children[:bab1_idx]:
                if elem.tag.endswith('}p'):
                    txt = ''.join(t.text or '' for t in elem.iter() if t.tag.endswith('}t')).upper().strip()
                    is_official_header = any(txt.startswith(kw) for kw in ["LAMPIRAN", "PERATURAN BUPATI", "NOMOR", "TENTANG", "RENCANA KERJA PERANGKAT DAERAH"])
                    if not is_official_header:
                        elements_to_remove.append(elem)
                elif elem.tag.endswith('}tbl') or elem.tag.endswith('}sdt'):
                    elements_to_remove.append(elem)

            for elem in elements_to_remove:
                try:
                    body.remove(elem)
                except Exception:
                    pass
        except Exception:
            pass

    # 3. Sweep tambahan: Hapus paragraf tersisa sebelum BAB I yang masih mengandung kata kunci front matter atau TOC
    for p in list(doc.paragraphs[:15]):
        txt = clean_text_utf8(p.text).upper().strip()
        is_official_header = any(txt.startswith(kw) for kw in ["LAMPIRAN", "PERATURAN BUPATI", "NOMOR", "TENTANG", "RENCANA KERJA PERANGKAT DAERAH"])
        if not is_official_header:
            if re.search(r'^\s*BAB\s+(I|1)\b', txt) or re.search(r'^\s*BAB\s+I\s*[-–:]?\s*PENDAHULUAN', txt):
                break
            if any(kw in txt for kw in ["KATA PENGANTAR", "DAFTAR ISI", "DAFTAR TABEL", "DAFTAR GAMBAR", "DAFTAR GRAFIK", "DAFTAR LAMPIRAN", "COVER"]) or is_toc_or_leader_line(txt):
                try:
                    p._element.getparent().remove(p._element)
                except Exception:
                    pass


# ============================================================================
# NORMALISASI GAMBAR, DIAGRAM, DAN DRAWING (ANTI-OVERLAP)
# ============================================================================
def normalize_drawings_and_images(doc):
    """
    Konversi semua gambar/diagram dari floating (wp:anchor) menjadi inline (wp:inline),
    dan reset text wrapping agar gambar tidak tumpang tindih dengan teks.
    
    DrawingML anchor elements memiliki posisi absolut yang menyebabkan overlap.
    Dengan mengubahnya menjadi inline, gambar akan mengalir bersama teks.
    """
    body = doc._body._element
    
    # Namespace map untuk XPath
    nsmap = {
        'w': 'http://schemas.openxmlformats.org/wordprocessingml/2006/main',
        'wp': 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing',
        'a': 'http://schemas.openxmlformats.org/drawingml/2006/main',
        'r': 'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
        'wp14': 'http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing',
    }
    
    # 1. Cari semua wp:anchor (floating images/drawings)
    try:
        anchors = body.xpath('.//wp:anchor', namespaces=nsmap)
        for anchor in list(anchors):
            try:
                # Ambil dimensi dari anchor
                extent = anchor.find('wp:extent', nsmap)
                cx = extent.get('cx', '0') if extent is not None else '0'
                cy = extent.get('cy', '0') if extent is not None else '0'
                
                # Ambil graphic element (isi gambar sebenarnya)
                graphic = anchor.find('.//a:graphic', nsmap)
                if graphic is None:
                    continue
                
                # Ambil docPr (properti dokumen) jika ada
                docPr = anchor.find('wp:docPr', nsmap)
                
                # Buat elemen wp:inline baru
                inline_ns = 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing'
                inline = etree.SubElement(anchor.getparent(), '{%s}inline' % inline_ns)
                
                # Set dimensi
                inline_extent = etree.SubElement(inline, '{%s}extent' % inline_ns)
                inline_extent.set('cx', cx)
                inline_extent.set('cy', cy)
                
                # Set effectExtent (tanpa efek)
                eff_ext = etree.SubElement(inline, '{%s}effectExtent' % inline_ns)
                eff_ext.set('l', '0')
                eff_ext.set('t', '0')
                eff_ext.set('r', '0')
                eff_ext.set('b', '0')
                
                # Copy docPr jika ada
                if docPr is not None:
                    inline.append(deepcopy(docPr))
                else:
                    new_docPr = etree.SubElement(inline, '{%s}docPr' % inline_ns)
                    new_docPr.set('id', '1')
                    new_docPr.set('name', 'Image')
                
                # Copy graphic content
                inline.append(deepcopy(graphic))
                
                # Ganti anchor dengan inline di parent drawing
                parent = anchor.getparent()
                parent.replace(anchor, inline)
                
            except Exception:
                pass
    except Exception:
        pass
    
    # 2. Pastikan semua drawing paragraf memiliki spacing yang benar
    for p in doc.paragraphs:
        has_drawing = any(
            elem.tag.endswith(('drawing', 'Drawing'))
            for elem in p._element.iter()
        )
        if has_drawing:
            p.paragraph_format.space_before = Pt(6)
            p.paragraph_format.space_after = Pt(6)
            p.alignment = WD_ALIGN_PARAGRAPH.CENTER


def fix_vml_shapes(doc):
    """
    Tangani VML shapes (w:pict, v:shape) yang menggunakan posisi absolut.
    Reset position style agar tidak overlap dengan teks.
    """
    body = doc._body._element
    
    try:
        # Cari semua elemen VML (w:pict yang berisi v:shape)
        pict_elements = body.xpath('.//*[local-name()="pict"]')
        
        for pict in list(pict_elements):
            try:
                # Cari v:shape di dalamnya
                shapes = pict.xpath('.//*[local-name()="shape"]')
                for shape in shapes:
                    style = shape.get('style', '')
                    if not style:
                        continue
                    
                    # Hapus position:absolute yang menyebabkan overlap
                    style = re.sub(r'position\s*:\s*absolute\s*;?', '', style, flags=re.IGNORECASE)
                    
                    # Hapus margin-left dan margin-top absolute positioning
                    style = re.sub(r'margin-left\s*:\s*-?[\d.]+p[tx]\s*;?', '', style, flags=re.IGNORECASE)
                    style = re.sub(r'margin-top\s*:\s*-?[\d.]+p[tx]\s*;?', '', style, flags=re.IGNORECASE)
                    
                    # Hapus z-index yang menyebabkan layering issues
                    style = re.sub(r'z-index\s*:\s*-?\d+\s*;?', '', style, flags=re.IGNORECASE)
                    
                    # Hapus mso-position-horizontal/vertical absolute
                    style = re.sub(r'mso-position-horizontal\s*:\s*absolute\s*;?', '', style, flags=re.IGNORECASE)
                    style = re.sub(r'mso-position-vertical\s*:\s*absolute\s*;?', '', style, flags=re.IGNORECASE)
                    
                    # Bersihkan semicolon ganda
                    style = re.sub(r';\s*;+', ';', style).strip().rstrip(';')
                    
                    shape.set('style', style)
                
                # Cari v:textbox dan pastikan teks di dalamnya ter-format
                textboxes = pict.xpath('.//*[local-name()="textbox"]')
                for textbox in textboxes:
                    # Set inset agar teks tidak terlalu mepet
                    textbox.set('inset', '7.2pt,3.6pt,7.2pt,3.6pt')
                    
            except Exception:
                pass
    except Exception:
        pass
    
    # Tangani juga w10:wrap dan wp:wrapNone/wrapSquare/wrapTight
    try:
        # Hapus wrap elements yang menyebabkan floating
        for wrap_type in ['wrapNone', 'wrapSquare', 'wrapTight', 'wrapThrough']:
            wraps = body.xpath('.//*[local-name()="%s"]' % wrap_type)
            for wrap in list(wraps):
                try:
                    parent = wrap.getparent()
                    # Ganti dengan wrapTopAndBottom (paling aman, gambar di baris sendiri)
                    if parent is not None and parent.tag.endswith(('anchor',)):
                        new_wrap = OxmlElement('wp:wrapTopAndBottom')
                        parent.replace(wrap, new_wrap)
                except Exception:
                    pass
    except Exception:
        pass


def clean_textbox_overlaps(doc):
    """
    Deteksi dan perbaiki text box (VML dan DrawingML) yang overlap.
    Text box floating dengan posisi absolut dikonversi menjadi bordered paragraf biasa.
    """
    body = doc._body._element
    
    try:
        # 1. Cari semua w:txbxContent (isi text box)
        txbx_contents = body.xpath('.//*[local-name()="txbxContent"]')
        
        for txbx in list(txbx_contents):
            try:
                # Ambil semua paragraf dari text box
                paras = txbx.xpath('.//*[local-name()="p"]')
                if not paras:
                    continue
                    
                # Kumpulkan teks dari text box
                texts = []
                for p_elem in paras:
                    t_elements = p_elem.xpath('.//*[local-name()="t"]')
                    para_text = ''.join(t.text or '' for t in t_elements).strip()
                    if para_text:
                        texts.append(para_text)
                
                if not texts:
                    continue
                
                # Cari elemen w:drawing atau w:pict terdekat yang merupakan container
                container = txbx
                for _ in range(10):  # max 10 level up
                    parent = container.getparent()
                    if parent is None:
                        break
                    if parent.tag.endswith(('}drawing', '}pict')):
                        container = parent
                        break
                    container = parent
                
                # Cari paragraf yang memuat container ini
                drawing_parent_p = container
                for _ in range(5):
                    pp = drawing_parent_p.getparent()
                    if pp is None:
                        break
                    if pp.tag.endswith('}p'):
                        drawing_parent_p = pp
                        break
                    drawing_parent_p = pp
                
            except Exception:
                pass
    except Exception:
        pass
    
    # 2. Perbaiki paragraf yang berisi drawing agar memiliki spacing yang tepat
    for p in doc.paragraphs:
        has_drawing = False
        for elem in p._element.iter():
            tag = elem.tag
            if tag.endswith(('drawing', 'pict', 'Drawing')):
                has_drawing = True
                break
        
        if has_drawing:
            # Pastikan paragraf dengan gambar punya spacing yang cukup
            p.paragraph_format.space_before = Pt(6)
            p.paragraph_format.space_after = Pt(6)
            
            # Pastikan keep_with_next false agar tidak memaksa gambar menempel ke paragraf berikutnya
            p.paragraph_format.keep_with_next = False
            
            # Set paragraph alignment center untuk gambar/diagram
            if not p.text.strip():
                p.alignment = WD_ALIGN_PARAGRAPH.CENTER


# ============================================================================
# FUNGSI UTAMA: process_document & format_document
# ============================================================================
def inject_official_header_block(doc, romawi_header, opd_name, tahun, template_code="RENJA"):
    """
    Header Lampiran Resmi Perbup Cirebon (5 baris) HANYA BERLAKU UNTUK TEMPLATE RENJA.
    Untuk RKPD, Evaluasi RKPD, atau template lainnya, fungsi ini TIDAK MENYISIPKAN HEADER ini.
    """
    # Hapus paragraf header lama jika ada di 10 paragraf awal
    for p in list(doc.paragraphs[:10]):
        txt = p.text.upper().strip()
        if any(txt.startswith(kw) for kw in ["LAMPIRAN", "PERATURAN BUPATI", "NOMOR", "TENTANG", "RENCANA KERJA PERANGKAT DAERAH"]):
            try:
                p._element.getparent().remove(p._element)
            except Exception:
                pass

    if str(template_code).upper() != 'RENJA':
        return

    romawi_clean = romawi_header.upper().replace('_', ' ').strip()
    if not romawi_clean.startswith('LAMPIRAN'):
        romawi_clean = f"LAMPIRAN {romawi_clean}"

    tahun_renja = str(tahun).strip()
    try:
        tahun_perbup = str(int(tahun_renja) - 1) if int(tahun_renja) > 2000 else "2026"
    except Exception:
        tahun_perbup = "2026"

    header_lines = [
        romawi_clean,
        "PERATURAN BUPATI CIREBON",
        f"NOMOR            TAHUN {tahun_perbup}",
        "TENTANG",
        f"RENCANA KERJA PERANGKAT DAERAH TAHUN {tahun_renja}"
    ]

    # Injeksi 5 baris header baru (Teks RATA KIRI dengan Left Indent 95mm di separuh kanan kertas)
    if len(doc.paragraphs) > 0:
        first_p = doc.paragraphs[0]
        for line in header_lines:
            p = first_p.insert_paragraph_before(line)
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT
            p.paragraph_format.left_indent = Mm(95)
            p.paragraph_format.space_before = Pt(0)
            p.paragraph_format.space_after = Pt(0)
            p.paragraph_format.line_spacing = 1.15
            for run in p.runs:
                run.bold = False
                run.font.name = 'Bookman Old Style'
                run.font.size = Pt(12)

        # Spasi pemisah setelah header block
        spacer = first_p.insert_paragraph_before("")
        spacer.paragraph_format.space_before = Pt(0)
        spacer.paragraph_format.space_after = Pt(12)
    else:
        for line in header_lines:
            p = doc.add_paragraph(line)
            p.alignment = WD_ALIGN_PARAGRAPH.LEFT
            p.paragraph_format.left_indent = Mm(95)
            p.paragraph_format.space_before = Pt(0)
            p.paragraph_format.space_after = Pt(0)
            p.paragraph_format.line_spacing = 1.15
            for run in p.runs:
                run.bold = False
                run.font.name = 'Bookman Old Style'
                run.font.size = Pt(12)
        doc.add_paragraph("")

def process_document(input_path, output_path, template_code="RENJA"):
    """
    Fungsi utama yang memproses dokumen input, mendeteksi tabel matriks,
    mengatur section break, memformat page breaks, dan menyimpan hasilnya.
    """
    if not os.path.exists(input_path):
        raise FileNotFoundError(f"File input '{input_path}' tidak ditemukan.")

    doc = docx.Document(input_path)
    tmpl_code = str(template_code).upper().strip()

    parsed_romawi, parsed_opd, parsed_tahun = parse_document_metadata(doc)

    if tmpl_code == 'RENJA':
        strip_renja_front_matter(doc)
        inject_official_header_block(doc, parsed_romawi.replace('_', ' '), parsed_opd.replace('_', ' '), parsed_tahun, tmpl_code)
        clear_all_headers_and_footers(doc)
        strip_all_bold_xml_nodes(doc)

    add_page_break_before_chapters(doc)
    normalize_drawings_and_images(doc)
    fix_vml_shapes(doc)
    clean_textbox_overlaps(doc)
    apply_global_font_reset(doc)
    process_layout_aware_table_autofix(doc)

    os.makedirs(os.path.dirname(os.path.abspath(output_path)), exist_ok=True)
    doc.save(output_path)
    print(f"OUTPUT_FILE:{output_path}")

def format_document(input_path, output_dir_or_file=None, arg_romawi=None, arg_opd=None, arg_tahun=None, arg_template="RENJA"):
    if not os.path.exists(input_path):
        print(f"Error: File input '{input_path}' tidak ditemukan.")
        sys.exit(1)

    doc = docx.Document(input_path)
    template_code = str(arg_template or "RENJA").upper().strip()

    parsed_romawi, parsed_opd, parsed_tahun = parse_document_metadata(
        doc,
        default_romawi=arg_romawi or "LAMPIRAN_LIII",
        default_opd=arg_opd or "Kecamatan_Losari",
        default_tahun=arg_tahun or "2027"
    )

    romawi_str = arg_romawi if arg_romawi else parsed_romawi
    opd_str = arg_opd if arg_opd else parsed_opd
    tahun_val = arg_tahun or parsed_tahun or "2027"

    filename = build_output_filename(romawi_str, opd_str, tahun_val)
    if filename is None:
        base_name = os.path.basename(input_path)
        name_only, ext = os.path.splitext(base_name)
        filename = f"{name_only}_Diformat{ext}"

    if output_dir_or_file and os.path.isdir(output_dir_or_file):
        output_path = os.path.join(output_dir_or_file, filename)
    elif output_dir_or_file and output_dir_or_file.endswith('.docx'):
        output_path = output_dir_or_file
    else:
        output_path = os.path.join(os.path.dirname(input_path), filename)

    opd_clean_display = opd_str.replace('_', ' ').strip()
    romawi_clean_display = romawi_str.replace('_', ' ').strip()

    # LOGIKA KONDISIONAL BERBASIS TEMPLATE
    if template_code == 'RENJA':
        # 1. Hapus Front Matter non-Renja (Cover, Kata Pengantar, Daftar Isi/Tabel/Gambar) sebelum BAB I
        strip_renja_front_matter(doc)
        # 2. Injeksi Header Block Resmi Perbup Cirebon pada halaman 1
        inject_official_header_block(doc, romawi_clean_display, opd_clean_display, tahun_val, template_code)
        # 3. Kosongkan seluruh Header & Footer
        clear_all_headers_and_footers(doc)
        # 4. Hapus seluruh atribut Bold
        strip_all_bold_xml_nodes(doc)

    # PAGE SETUP & LAYOUT ENFORCEMENT UNTUK SELURUH SECTION
    enforce_all_xml_section_properties(doc)
    add_page_break_before_chapters(doc)
    normalize_drawings_and_images(doc)
    fix_vml_shapes(doc)
    clean_textbox_overlaps(doc)
    apply_global_font_reset(doc)
    process_layout_aware_table_autofix(doc)

    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    doc.save(output_path)

    print(f"OUTPUT_FILE:{output_path}")
    print(f"Sukses: Dokumen berhasil diformat dan disimpan ke '{output_path}'.")

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Penggunaan: python formatter.py <input_path> [output_path_or_dir] [romawi_header] [opd_name] [tahun] [template_code]")
        sys.exit(1)

    input_file = sys.argv[1]
    output_target = sys.argv[2] if len(sys.argv) > 2 else None
    romawi_arg = sys.argv[3] if len(sys.argv) > 3 else None
    opd_arg = sys.argv[4] if len(sys.argv) > 4 else None
    tahun_arg = sys.argv[5] if len(sys.argv) > 5 else None
    template_arg = sys.argv[6] if len(sys.argv) > 6 else "RENJA"

    format_document(input_file, output_target, romawi_arg, opd_arg, tahun_arg, template_arg)
