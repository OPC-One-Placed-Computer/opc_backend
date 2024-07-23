@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="text-center">
            <div id="success-animation" style="width: 300px; height: 300px; margin: 0 auto;"></div>
            <h1 class="text-success display-4">Payment Successful</h1>
            <p class="lead">Your payment was successful. Thank you for your order!</p>
            <p class="lead">Please close this tab.</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            bodymovin.loadAnimation({
                container: document.getElementById('success-animation'),
                path: '{{ asset('animations/success-animation.json') }}',
                renderer: 'svg',
                loop: true,
                autoplay: true,
                name: "Success Animation",
            });
        });
    </script>
@endsection
