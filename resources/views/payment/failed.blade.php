<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Failed</title>
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
            border-top: 6px solid #ef4444; /* Error Red */
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
            background: #ef4444;
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-circle">✕</div>
        <h1>Payment Failed</h1>
        <p>We couldn't process your transaction. This could be due to insufficient funds or an issue with your bank. Please check your details and try again.</p>
{{--         <button onclick="window.location.href='myapp://payment-failed'">Return to medConnect</button>
 --}}        <a class="btn danger" href="myapp://payment-failed">Return to medConnect</a>

   </div>
</body>
</html>
