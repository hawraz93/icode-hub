<!DOCTYPE html>
<html lang="ckb" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'وەسڵی فەرمی' }} | iCode Group</title>
    
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    
    @vite(['resources/css/app.css'])
    
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body class="bg-white text-slate-900 font-sans p-4 sm:p-8">
    {{ $slot }}
</body>
</html>
