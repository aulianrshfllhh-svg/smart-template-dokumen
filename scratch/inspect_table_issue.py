import docx
import os
import sys

src = 'storage/app/private/renja/2027/kecamatan-depok/renja-murni/original/6a859b9a61741_1. Renja Akhir 2027 Kec Depok.docx'
if not os.path.exists(src):
    print("File not found:", src)
    sys.exit(1)

doc = docx.Document(src)
print("Doc sections:", len(doc.sections))
for i, s in enumerate(doc.sections):
    print(f"Section {i}: orient={s.orientation}, w={s.page_width.mm:.1f}mm, h={s.page_height.mm:.1f}mm, left={s.left_margin.mm:.1f}mm, right={s.right_margin.mm:.1f}mm")

for t_idx, tbl in enumerate(doc.tables):
    txt = ''.join(tbl._tbl.itertext())[:60].replace('\n', ' ')
    print(f"\n--- TABLE {t_idx} ---")
    print(f"Rows: {len(tbl.rows)}, Cols: {len(tbl.columns)}, Preview: {txt}")
    
    # Check tblPr
    tblPr = tbl._tbl.tblPr
    if tblPr is not None:
        jc = tblPr.find('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}jc')
        tblInd = tblPr.find('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}tblInd')
        tblW = tblPr.find('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}tblW')
        tblpPr = tblPr.find('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}tblpPr')
        print(f"  jc: {jc.attrib if jc is not None else 'None'}")
        print(f"  tblInd: {tblInd.attrib if tblInd is not None else 'None'}")
        print(f"  tblW: {tblW.attrib if tblW is not None else 'None'}")
        print(f"  tblpPr (floating?): {tblpPr.attrib if tblpPr is not None else 'None'}")

    # Check gridCols
    if tbl._tbl.tblGrid is not None:
        gridCols = tbl._tbl.tblGrid.gridCol_lst
        widths_dxa = [col.w for col in gridCols]
        total_mm = sum(widths_dxa) / 56.7
        print(f"  GridCols ({len(gridCols)}): total={total_mm:.1f}mm ({sum(widths_dxa)} dxa)")
        print(f"  Widths (mm): {[round(w/56.7, 1) for w in widths_dxa]}")
