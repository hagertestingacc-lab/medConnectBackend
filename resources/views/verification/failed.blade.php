<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Failed</title>
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #fef2f2; /* Very light red */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .card {
            background: #ffffff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            text-align: center;
            max-width: 400px;
            border-top: 6px solid #ef4444;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 40px;
            margin: 0 auto 20px;
        }
        h1 { color: #991b1b; margin: 0 0 10px; font-size: 24px; }
        p { color: #4b5563; line-height: 1.5; margin-bottom: 25px; }
        .btn {
            display: block;
            background: #374151; /* Neutral dark for secondary action */
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
        }
        .secondary-link {
            display: inline-block;
            margin-top: 15px;
            color: #ef4444;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle">!</div>
        <h1>Link Invalid</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>
