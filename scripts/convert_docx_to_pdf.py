import sys
import os
import subprocess

def convert_docx_to_pdf(input_docx, output_pdf):
    input_docx = os.path.abspath(input_docx)
    output_pdf = os.path.abspath(output_pdf)

    if not os.path.exists(input_docx):
        print(f"Error: Input file {input_docx} not found", file=sys.stderr)
        return False

    out_dir = os.path.dirname(output_pdf)
    if not os.path.exists(out_dir):
        os.makedirs(out_dir, exist_ok=True)

    # Method 1: Try PowerShell COM script
    ps_script = os.path.join(os.path.dirname(__file__), 'convert_docx_to_pdf.ps1')
    if os.path.exists(ps_script):
        cmd = [
            'powershell', '-NoProfile', '-ExecutionPolicy', 'Bypass',
            '-File', ps_script,
            '-InputDocx', input_docx,
            '-OutputPdf', output_pdf
        ]
        res = subprocess.run(cmd, capture_output=True, text=True)
        if res.returncode == 0 and os.path.exists(output_pdf) and os.path.getsize(output_pdf) > 1000:
            print(f"SUCCESS: Converted via PowerShell COM to {output_pdf}")
            return True

    # Method 2: Try LibreOffice
    for lo_cmd in ['soffice', 'libreoffice', r'C:\Program Files\LibreOffice\program\soffice.exe']:
        try:
            res = subprocess.run([lo_cmd, '--headless', '--convert-to', 'pdf', '--outdir', out_dir, input_docx], capture_output=True, text=True)
            expected_pdf = os.path.join(out_dir, os.path.splitext(os.path.basename(input_docx))[0] + '.pdf')
            if os.path.exists(expected_pdf) and os.path.getsize(expected_pdf) > 1000:
                if expected_pdf != output_pdf:
                    os.replace(expected_pdf, output_pdf)
                print(f"SUCCESS: Converted via LibreOffice to {output_pdf}")
                return True
        except Exception:
            continue

    return False

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print("Usage: python convert_docx_to_pdf.py <input.docx> <output.pdf>", file=sys.stderr)
        sys.exit(1)

    success = convert_docx_to_pdf(sys.argv[1], sys.argv[2])
    sys.exit(0 if success else 1)
