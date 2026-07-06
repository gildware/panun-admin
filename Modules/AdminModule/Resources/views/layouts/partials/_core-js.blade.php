{{-- Loaded once before turbo-frame page scripts to avoid duplicate Bootstrap/jQuery handlers. --}}
<script src="{{ asset('assets/admin-module') }}/js/jquery-3.6.0.min.js"></script>
<script src="{{ asset('assets/admin-module') }}/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/admin-module') }}/js/bootstrap-jquery-modal-bridge.js?v={{ $adminAssetVersion ?? time() }}"></script>
<script src="{{ asset('assets/admin-module') }}/plugins/select2/select2.min.js"></script>
