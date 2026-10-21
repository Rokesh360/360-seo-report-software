@php
    $successMessage = session('success');
    $s    = session('spend_summary');
    $over = $s && $s['available'] < 0;
@endphp

@if($successMessage || $s)
<script>
(function () {
    const show = () => Swal.fire({
        icon: {!! $over ? "'warning'" : "'success'" !!},
        title: @json($successMessage ?: 'Report saved'),
        html: `
            @if($s)
            <table style="margin:0 auto;text-align:left;font-size:15px;">
                <tr>
                    <td style="padding:4px 14px 4px 0;">Client budget</td>
                    <td><strong>₹{{ number_format($s['budget']) }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:4px 14px 4px 0;">Amount spent</td>
                    <td><strong>₹{{ number_format($s['spent']) }}</strong></td>
                </tr>
                <tr>
                    <td style="padding:4px 14px 4px 0;">Available</td>
                    <td><strong style="color:{{ $over ? '#d33' : '#16a34a' }};">
                        {{ $over ? '-' : '' }}₹{{ number_format(abs($s['available'])) }}
                    </strong></td>
                </tr>
            </table>
            @if($over)
                <p style="color:#d33;margin-top:12px;">Spend exceeds the client budget.</p>
            @endif
            @endif
        `,
        confirmButtonText: 'OK',
    });

    if (window.Swal) { show(); return; }
    const el = document.createElement('script');
    el.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
    el.onload = show;
    document.head.appendChild(el);
})();
</script>
@endif