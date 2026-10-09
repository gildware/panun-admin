@php
    $providedTitle = isset($pageTitle) ? trim((string) $pageTitle) : '';
    $rawTitle = $providedTitle !== ''
        ? $providedTitle
        : trim((string) $__env->yieldContent('title'));
    $rawTitle = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($rawTitle))));
    $brand = 'Panun Kaergar';
    if ($rawTitle === '') {
        $documentTitle = $brand.' Admin';
    } elseif (stripos($rawTitle, $brand) !== false) {
        $documentTitle = $rawTitle;
    } else {
        $documentTitle = $rawTitle.' | '.$brand;
    }
@endphp
<title>{{ $documentTitle }}</title>
<meta name="application-name" content="Panun Kaergar Admin">
<meta name="apple-mobile-web-app-title" content="Panun Admin">
<meta name="theme-color" content="#202048">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" href="{{ asset('assets/admin-module/img/favicon.svg') }}" type="image/svg+xml">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/admin-module/img/favicon-32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('assets/admin-module/img/apple-touch-icon.png') }}">
