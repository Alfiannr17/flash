<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'FlashOtp') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

        <!-- 🌟 KODE UNTUK DESKRIPSI SAAT DI-SHARE (SEO & OPEN GRAPH) 🌟 -->
    <meta name="description" content="FlashOTP - Layanan OTP Virtual Number Tercepat dan Termurah untuk pendaftaran berbagai aplikasi seperti WhatsApp, Telegram, Instagram, dan lainnya.">

    <!-- Open Graph / WhatsApp / Facebook / Telegram -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://flashotp.shop/">
    <meta property="og:title" content="FlashOTP - Solusi Nomor Virtual Cepat & Murah">
    <meta property="og:description" content="Topup instan via QRIS. Dapatkan nomor virtual untuk WhatsApp, Telegram, dan aplikasi lainnya dengan harga termurah dan server paling stabil.">
    <!-- Gambar yang muncul pas di-share. Bisa pakai favicon atau bikin banner khusus (misal banner.jpg) di folder public -->
    <meta property="og:image" content="{{ asset('favicon.png') }}"> 

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://flashotp.shop/">
    <meta property="twitter:title" content="FlashOTP - Solusi Nomor Virtual Cepat & Murah">
    <meta property="twitter:description" content="Topup instan via QRIS. Dapatkan nomor virtual untuk WhatsApp, Telegram, dan aplikasi lainnya dengan harga termurah.">
    <meta property="twitter:image" content="{{ asset('favicon.png') }}">
    <!-- ========================================================== -->

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
