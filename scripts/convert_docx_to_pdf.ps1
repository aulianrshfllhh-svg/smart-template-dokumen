param (
    [Parameter(Mandatory=$true)]
    [string]$InputDocx,
    [Parameter(Mandatory=$true)]
    [string]$OutputPdf
)

$InputDocx = [System.IO.Path]::GetFullPath($InputDocx)
$OutputPdf = [System.IO.Path]::GetFullPath($OutputPdf)

if (-not (Test-Path $InputDocx)) {
    Write-Error "Input file not found: $InputDocx"
    exit 1
}

$outputDir = [System.IO.Path]::GetDirectoryName($OutputPdf)
if (-not (Test-Path $outputDir)) {
    New-Item -ItemType Directory -Path $outputDir -Force | Out-Null
}

$word = $null
$doc = $null

try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0 # wdAlertsNone

    # Open document read-only
    $doc = $word.Documents.Open($InputDocx, $false, $true)

    # 17 = wdFormatPDF
    # ExportAsFixedFormat(OutputFileName, ExportFormat, OpenAfterExport, OptimizeFor, Range, From, To, Item, IncludeDocProps, KeepIRM, CreateBookmarks, DocStructureTags, BitmapMissingFonts, UseISO19005_1)
    $wdExportFormatPDF = 17
    $doc.ExportAsFixedFormat(
        $OutputPdf,
        $wdExportFormatPDF,
        $false, # OpenAfterExport
        0,      # wdExportOptimizeForPrint
        0,      # wdExportAllDocument
        1,      # From
        1,      # To
        0,      # wdExportDocumentContent
        $true,  # IncludeDocProps
        $true,  # KeepIRM
        1,      # wdExportCreateHeadingBookmarks
        $true,  # DocStructureTags
        $true,  # BitmapMissingFonts
        $false  # UseISO19005_1
    )

    if (Test-Path $OutputPdf) {
        Write-Output "SUCCESS: PDF generated at $OutputPdf (Size: $((Get-Item $OutputPdf).Length) bytes)"
        exit 0
    } else {
        Write-Error "Failed to generate PDF."
        exit 1
    }
} catch {
    Write-Error "Conversion error: $_"
    exit 1
} finally {
    if ($doc -ne $null) {
        $doc.Close([ref]$false)
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($doc) | Out-Null
    }
    if ($word -ne $null) {
        $word.Quit()
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
    }
    [System.GC]::Collect()
    [System.GC]::WaitForPendingFinalizers()
}
