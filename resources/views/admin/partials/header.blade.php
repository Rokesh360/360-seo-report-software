<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $documentTitle ?? $pageTitle }} | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|space-grotesk:500,600,700" rel="stylesheet">
    @if(!empty($includeDataTables))
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.4/css/dataTables.dataTables.min.css">
    @endif
    @vite($viteAssets ?? ['resources/css/app.css', 'resources/js/app.js'])
    @if(!empty($includeBootstrap))
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @endif
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="{{ $bodyClass ?? 'admin-page' }}">
    <div class="admin-shell">
        @include('admin.partials.sidebar', ['active' => $active ?? 'overview'])
        <main class="admin-main" @if(!empty($mainId)) id="{{ $mainId }}" @endif>
            <header class="topbar">
                <div class="breadcrumbs"><span>Workspace</span><span class="crumb-slash">/</span><strong>{{ $pageTitle }}</strong></div>
                <div class="topbar-right">
                    <span class="report-period"><span class="period-dot"></span> {{ $periodLabel ?? now()->format('F Y') }}</span>
                    <span class="topbar-divider"></span>
                    <span class="avatar-small">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
            </header>