import docx

src = 'storage/app/private/renja/2027/kecamatan-depok/renja-murni/original/6a859b9a61741_1. Renja Akhir 2027 Kec Depok.docx'
doc = docx.Document(src)
tbl0 = doc.tables[0]

row3 = tbl0.rows[3]
total_w = 0
for idx, cell in enumerate(row3.cells):
    tcPr = cell._tc.tcPr
    tcW = tcPr.find('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}tcW')
    w_val = int(tcW.get('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}w')) if tcW is not None else 0
    total_w += w_val
    txt = cell.text.strip()
    header_txt = tbl0.rows[0].cells[idx].text.strip().replace('\n', ' ')
    print(f"Col {idx:2d}: no={txt:2s}, w={w_val:5d} dxa ({w_val/56.7:5.1f} mm), header='{header_txt[:30]}'")

print(f"\nTotal width from tcW: {total_w} dxa ({total_w/56.7:.1f} mm)")
print(f"tblW: {tbl0._tbl.tblPr.find('{http://schemas.openxmlformats.org/wordprocessingml/2006/main}tblW').attrib}")
