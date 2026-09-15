Set-Location -Path "d:\erenja-app"
Write-Host "Menjalankan PHP Artisan Serve..." -ForegroundColor Green
& "C:\xampp\php\php.exe" artisan serve --host=127.0.0.1 --port=8000
Write-Host "Server terhenti. Tekan Enter untuk keluar..." -ForegroundColor Red
Read-Host
