<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Framework Exception</title>
    <style>
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #1a1b26; color: #a9b1d6; direction: ltr; }
        .error-header { background-color: #f7768e; padding: 40px; color: #1a1b26; border-bottom: 5px solid #db4b4b; }
        .error-header h1 { margin: 0; font-size: 32px; font-weight: 700; }
        .error-header p { margin: 10px 0 0 0; font-size: 18px; font-weight: 500; opacity: 0.9; }
        .error-container { padding: 40px; max-width: 1200px; margin: 0 auto; }
        .error-section { background-color: #24283b; border-radius: 8px; padding: 25px; margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.3); border: 1px solid #414868; }
        .error-section h3 { margin-top: 0; color: #7aa2f7; font-size: 20px; margin-bottom: 15px; border-bottom: 1px solid #414868; padding-bottom: 10px; }
        .file-info { background-color: #1f2335; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 16px; color: #e0af68; border-left: 4px solid #ff9e64; margin-bottom: 20px; }
        pre { background-color: #1f2335; color: #c0caf5; padding: 20px; border-radius: 6px; overflow-x: auto; font-size: 14px; line-height: 1.6; margin: 0; border: 1px solid #292e42; }
    </style>
</head>
<body>
    <div class='error-header'>
        <h1>Yurni Exception Occurred</h1>
        <p><?= htmlspecialchars($e->getMessage()) ?></p>
    </div>
    <div class='error-container'>
        <div class='error-section'>
            <h3>Exception Location</h3>
            <div class='file-info'>
                <strong>File:</strong> <?= htmlspecialchars($e->getFile()) ?><br>
                <strong>Line:</strong> <?= $e->getLine() ?>
            </div>
        </div>
        <div class='error-section'>
            <h3>Stack Trace</h3>
            <pre><?= htmlspecialchars($e->getTraceAsString()) ?></pre>
        </div>
    </div>
</body>
</html>
