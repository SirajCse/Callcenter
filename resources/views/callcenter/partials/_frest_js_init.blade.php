{{-- Call Center JS bootstrap — sets the CSRF header once for every AJAX call on the page. --}}
<script>
$.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
});
</script>
