import sys
import glob

files = glob.glob('storage/app/preview_cache/preview_lampiran_murni_12_*.pdf')
if not files:
    print("No PDF file found")
    sys.exit(1)

pdf_path = files[0]
print(f"Reading PDF: {pdf_path}")

try:
    import pypdf
    reader = pypdf.PdfReader(pdf_path)
    for i, page in enumerate(reader.pages):
        print(f"--- PAGE {i+1} ---")
        print(page.extract_text())
except Exception as e:
    print(f"pypdf error: {e}")
    # Fallback to pdfplumber or plain string search
    try:
        import pdfplumber
        with pdfplumber.open(pdf_path) as pdf:
            for i, page in enumerate(pdf.pages):
                print(f"--- PAGE {i+1} ---")
                print(page.extract_text())
    except Exception as e2:
        print(f"pdfplumber error: {e2}")
