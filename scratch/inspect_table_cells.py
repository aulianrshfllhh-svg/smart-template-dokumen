import docx

src = 'storage/app/private/renja/2027/kecamatan-depok/renja-murni/original/6a859b9a61741_1. Renja Akhir 2027 Kec Depok.docx'
doc = docx.Document(src)
tbl0 = doc.tables[0]

print(f"Table 0 has {len(tbl0.rows)} rows and {len(tbl0.columns)} cols.")
for r_idx in range(min(5, len(tbl0.rows))):
    row = tbl0.rows[r_idx]
    cells_info = []
    for c_idx, cell in enumerate(row.cells):
        tcPr = cell._tc.tcPr
        tcW = tcPr.find('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}tcW')
        w_val = tcW.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}w') if tcW is not None else 'None'
        w_type = tcW.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}type') if tcW is not None else 'None'
        txt = cell.text.strip().replace('\n', ' ')[:20]
        cells_info.append(f"[C{c_idx}: w={w_val} ({w_type}), txt='{txt}']")
    print(f"Row {r_idx} ({len(row.cells)} cells):")
    for ci in cells_info[:7]:
        print("  ", ci)
    print("   ... total cells:", len(cells_info))
