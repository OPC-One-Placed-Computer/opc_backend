@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="text-center">
            <div id="cancel-animation" style="width: 300px; height: 300px; margin: 0 auto;"></div>
            <h1 class="text-warning display-4">Payment Cancelled</h1>
            <p class="lead">Your payment was cancelled.</p>
            <p class="lead">Please close this tab.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bodymovin.loadAnimation({
                container: document.getElementById('cancel-animation'),
                path: '{{ asset('animations/cancel-animation.json') }}',
                renderer: 'svg',
                loop: true,
                autoplay: true,
                name: "Cancel Animation",
            });
        });
    </script>
@endsection
