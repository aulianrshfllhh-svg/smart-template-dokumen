import docx
from docx.shared import Mm, Pt
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.section import WD_ORIENTATION
from docx.oxml import OxmlElement, parse_xml
from docx.oxml.ns import qn, nsdecls
import re

ns = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main'

def optimize_table_for_page(tbl, available_width_dxa, font_name="Bookman Old Style"):
    """
    Rapi-kan tabel agar pas 100% dengan lebar halaman (portrait atau landscape)
    dan berpenampilan rapi seperti dokumen resmi Word:
    1. Hilangkan negative indent / floating positioning (tblpPr).
    2. Set tblW = available_width_dxa (type='dxa').
    3. Set tblInd = 0 (rata kiri margin 2cm).
    4. Set tblCellMar (padding atas/bawah/kiri/kanan yang pas).
    5. Hitung ulang & skala proporsional gridCol dan tcW agar sum == available_width_dxa.
    6. Tentukan font size optimal berdasarkan jumlah kolom:
       - Kolom >= 14 (misal Tabel 2.1 Rekapitulasi 19-20 kolom): 7.0 - 7.5 pt
       - Kolom 8-13 (misal Matriks Bab IV 8-9 kolom): 8.0 - 8.5 pt
       - Kolom < 8: 9.0 - 10.0 pt
    7. Set cantSplit pada setiap row dan tblHeader pada baris header (1-4).
    8. Set line spacing 1.0 (single) dan space before/after = 0 pt.
    """
    tblPr = tbl._tbl.tblPr
    if tblPr is None:
        tblPr = OxmlElement('w:tblPr')
        tbl._tbl.insert(0, tblPr)

    # 1. Hapus floating positioning jika ada
    tblpPr = tblPr.find(f'{{{ns}}}tblpPr')
    if tblpPr is not None:
        tblPr.remove(tblpPr)

    # 2. Set Alignment: Rata Kiri mengikuti margin halaman (2cm)
    jc = tblPr.find(f'{{{ns}}}jc')
    if jc is None:
        jc = OxmlElement('w:jc')
        tblPr.append(jc)
    jc.set(qn('w:val'), 'left')

    # 3. Set tblInd = 0 (dxa)
    tblInd = tblPr.find(f'{{{ns}}}tblInd')
    if tblInd is None:
        tblInd = OxmlElement('w:tblInd')
        tblPr.append(tblInd)
    tblInd.set(qn('w:w'), '0')
    tblInd.set(qn('w:type'), 'dxa')

    # 4. Set tblW
    tblW = tblPr.find(f'{{{ns}}}tblW')
    if tblW is None:
        tblW = OxmlElement('w:tblW')
        tblPr.append(tblW)
    tblW.set(qn('w:w'), str(int(available_width_dxa)))
    tblW.set(qn('w:type'), 'dxa')

    # 5. Set tblCellMar (cell margins / padding)
    # top=30 dxa (1.5pt), bottom=30 dxa (1.5pt), left=50 dxa (2.5pt), right=50 dxa (2.5pt)
    tblCellMar = tblPr.find(f'{{{ns}}}tblCellMar')
    if tblCellMar is None:
        tblCellMar = OxmlElement('w:tblCellMar')
        tblPr.append(tblCellMar)
    for side, val in [('top', '30'), ('bottom', '30'), ('left', '50'), ('right', '50')]:
        node = tblCellMar.find(f'{{{ns}}}{side}')
        if node is None:
            node = OxmlElement(f'w:{side}')
            tblCellMar.append(node)
        node.set(qn('w:w'), val)
        node.set(qn('w:type'), 'dxa')

    # 6. Hitung lebar kolom saat ini & lakukan scaling proporsional
    num_cols = len(tbl.columns)
    
    # Ambil lebar referensi dari gridCol jika ada, atau tcW baris pertama
    curr_widths = []
    if tbl._tbl.tblGrid is not None and len(tbl._tbl.tblGrid.gridCol_lst) > 0:
        for col in tbl._tbl.tblGrid.gridCol_lst:
            w_val = col.w
            # Jika w_val sangat besar (seperti dalam EMUs), konversi
            if w_val > 50000:
                # kemungkinan 1/360000 cm atau dxa / scale
                w_val = int(w_val / 56.7) # fallback
            curr_widths.append(float(w_val))
    else:
        # dari tcW baris dengan cell terbanyak
        max_cells_row = max(tbl.rows, key=lambda r: len(r.cells))
        for cell in max_cells_row.cells:
            tcPr = cell._tc.tcPr
            tcW = tcPr.find(f'{{{ns}}}tcW') if tcPr is not None else None
            w_val = float(tcW.get(qn('w:w'), '1000')) if tcW is not None else 1000.0
            curr_widths.append(w_val)

    if not curr_widths or len(curr_widths) != num_cols:
        curr_widths = [available_width_dxa / max(1, num_cols)] * num_cols

    total_curr_w = sum(curr_widths)
    if total_curr_w <= 0:
        total_curr_w = available_width_dxa

    scale_factor = available_width_dxa / total_curr_w
    new_widths_dxa = [int(round(w * scale_factor)) for w in curr_widths]
    
    # Pastikan jumlah pas persis dengan available_width_dxa
    diff = int(available_width_dxa) - sum(new_widths_dxa)
    if new_widths_dxa:
        new_widths_dxa[-1] += diff

    # Update tblGrid
    if tbl._tbl.tblGrid is not None:
        grid = tbl._tbl.tblGrid
        for col_el in list(grid.gridCol_lst):
            grid.remove(col_el)
        for w_dxa in new_widths_dxa:
            col_el = OxmlElement('w:gridCol')
            col_el.set(qn('w:w'), str(w_dxa))
            grid.append(col_el)

    # 7. Tentukan Font Size
    if num_cols >= 14:
        table_font_size_pt = 7.0
    elif num_cols >= 8:
        table_font_size_pt = 8.0
    elif num_cols >= 5:
        table_font_size_pt = 8.5
    else:
        table_font_size_pt = 9.5

    # 8. Terapkan pada setiap row & cell
    header_rows_count = 0
    # Deteksi jumlah baris header (biasanya baris yang punya cell bernomor 1,2,3... atau teks header)
    for r_idx, row in enumerate(tbl.rows[:5]):
        row_text = ' '.join(c.text.strip() for c in row.cells)
        if any(kw in row_text.upper() for kw in ['KODE', 'URUSAN', 'INDIKATOR', 'TARGET', 'REALISASI', 'PROGRAM/KEGIATAN', 'ASPEK', 'NO']):
            header_rows_count = r_idx + 1
        elif re.search(r'\b1\b.*\b2\b.*\b3\b', row_text): # baris penomoran kolom
            header_rows_count = r_idx + 1
            break

    for r_idx, row in enumerate(tbl.rows):
        trPr = row._tr.get_or_add_trPr()
        
        # cantSplit pada setiap row agar tidak terbelah jelek di tengah baris
        cantSplit = trPr.find(f'{{{ns}}}cantSplit')
        if cantSplit is None:
            cantSplit = OxmlElement('w:cantSplit')
            trPr.append(cantSplit)

        # tblHeader pada baris header agar berulang di setiap halaman
        if r_idx < header_rows_count:
            tblHeader = trPr.find(f'{{{ns}}}tblHeader')
            if tblHeader is None:
                tblHeader = OxmlElement('w:tblHeader')
                trPr.append(tblHeader)

        # Update tcW dan formatting teks pada setiap cell
        c_idx = 0
        for cell in row.cells:
            tcPr = cell._tc.get_or_add_tcPr()
            
            # Cari span (gridSpan) jika ada
            gridSpan = tcPr.find(f'{{{ns}}}gridSpan')
            span = int(gridSpan.get(qn('w:val'), '1')) if gridSpan is not None else 1
            
            if c_idx < len(new_widths_dxa):
                cell_w_dxa = sum(new_widths_dxa[c_idx:c_idx + span])
                tcW = tcPr.find(f'{{{ns}}}tcW')
                if tcW is None:
                    tcW = OxmlElement('w:tcW')
                    tcPr.append(tcW)
                tcW.set(qn('w:w'), str(int(cell_w_dxa)))
                tcW.set(qn('w:type'), 'dxa')
            
            c_idx += span

            # Format paragraf di dalam cell
            for p in cell.paragraphs:
                pPr = p._p.get_or_add_pPr()
                
                # Single line spacing & no space before/after
                p.paragraph_format.line_spacing = 1.0
                p.paragraph_format.space_before = Pt(1)
                p.paragraph_format.space_after = Pt(1)

                # Format runs
                for r in p.runs:
                    r.font.name = font_name
                    r.font.size = Pt(table_font_size_pt)
                    r.bold = False
                    
                    rPr = r._r.get_or_add_rPr()
                    for tag in ['b', 'bCs', 'color', 'highlight', 'shd', 'rStyle']:
                        el = rPr.find(f'{{{ns}}}{tag}')
                        if el is not None:
                            rPr.remove(el)
                    b = OxmlElement('w:b'); b.set(qn('w:val'), '0')
                    bCs = OxmlElement('w:bCs'); bCs.set(qn('w:val'), '0')
                    col = OxmlElement('w:color'); col.set(qn('w:val'), '000000')
                    rPr.extend([b, bCs, col])

print("optimize_table_for_page defined successfully.")
