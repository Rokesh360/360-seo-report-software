@if(session('spend_summary') && session('spend_summary')['spent'] > 0)
    @php
        $s    = session('spend_summary');
        $over = $s['available'] < 0;
    @endphp

    <script>
        (function () {
            const show = () => Swal.fire({
                icon: {{ $over ? "'warning'" : "'success'" }},
                title: @json(session('success', 'Report saved')),
                html: `
                    <table style="margin:0 auto;text-align:left;font-size:15px;">
                        <tr><td style="padding:4px 14px 4px 0;">Client budget</td>
                            <td><strong>₹{{ number_format($s['budget'], 2) }}</strong></td></tr>
                        <tr><td style="padding:4px 14px 4px 0;">Spend entered</td>
                            <td><strong>₹{{ number_format($s['spent'], 2) }}</strong></td></tr>
                        <tr><td style="padding:4px 14px 4px 0;">Available spend</td>
                            <td><strong style="color:{{ $over ? '#d33' : '#16a34a' }};">
                                {{ $over ? '-' : '' }}₹{{ number_format(abs($s['available']), 2) }}
                            </strong></td></tr>
                    </table>
                    @if($over)
                        <p style="color:#d33;margin-top:12px;">Spend exceeds the client budget.</p>
                    @endif
                `,
                confirmButtonText: 'OK',
            });

            if (window.Swal) { show(); return; }
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            s.onload = show;
            document.head.appendChild(s);
        })();
    </script>
@endif