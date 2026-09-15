<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sandbox F4 Editor</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            background-color: #cbd5e1; 
            height: 100vh; width: 100vw; 
            display: flex; flex-direction: column; align-items: center; 
            overflow-y: auto; padding: 40px 0; font-family: Arial, sans-serif;
        }
        .paper-f4 {
            width: 21.5cm; height: 33cm; 
            background-color: #ffffff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            padding: 2.5cm 2.5cm 2.5cm 3cm; 
            flex-shrink: 0; display: flex; flex-direction: column;
            position: relative; overflow: hidden;
        }
        .page-content {
            flex-grow: 1; outline: none; font-size: 12pt; line-height: 1.5; text-align: justify;
        }
        .header-locked { text-align: center; border-bottom: 2px solid black; margin-bottom: 20px; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="paper-f4">
        <div class="header-locked" contenteditable="false">
            <h2>BAB I</h2>
            <h2>PENDAHULUAN</h2>
        </div>
        <div class="page-content" contenteditable="true">
            <p>Jika kertas ini muncul di tengah dengan background abu-abu, berarti berhasil!</p>
        </div>
    </div>
</body>
</html>
