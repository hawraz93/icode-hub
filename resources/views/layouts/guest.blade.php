<!DOCTYPE html>
<html lang="ckb" dir="rtl" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'چوونەژوورەوە' }} | iCode Group Hub</title>
    
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    
    <!-- WireUI Scripts -->
    <wireui:scripts />
    
    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @livewireStyles
</head>
<body class="h-full bg-slate-950 text-slate-100 antialiased font-sans flex items-center justify-center min-h-screen relative overflow-hidden selection:bg-indigo-500 selection:text-white">
    
    <!-- Background Gradient Orbs -->
    <div class="fixed -top-40 -right-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="fixed -bottom-40 -left-40 w-96 h-96 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-cyan-600/10 rounded-full blur-[120px] pointer-events-none"></div>

    <!-- WireUI Notification & Dialog Mounts -->
    <x-dialog />
    <x-notifications />

    <div class="relative z-10 w-full max-w-md p-4 sm:p-6">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
