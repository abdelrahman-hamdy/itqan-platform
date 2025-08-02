@extends('layouts.academy')

@section('title', $academy->name . ' - ' . $academy->description)

@section('meta')
    <meta name="description" content="{{ $academy->description }}">
    <meta name="keywords" content="تعليم، قرآن، دورات، {{ $academy->name }}">
    <meta property="og:title" content="{{ $academy->name }}">
    <meta property="og:description" content="{{ $academy->description }}">
    <meta property="og:image" content="{{ $academy->logo_url }}">
@endsection

@section('content')
    <!-- Hero Section -->
    @include('academy.partials.hero-section')
    
    <!-- Stats Counter Section -->
    @include('academy.partials.stats-counter')
    
    <!-- Quran Services Section -->
    @include('academy.partials.quran-services')
    
    <!-- Academic Services Section -->
    @include('academy.partials.academic-services')
    
    <!-- Recorded Courses Section -->
    @include('academy.partials.recorded-courses')
    
    <!-- Footer -->
    @include('academy.partials.footer')
@endsection

@section('scripts')
    <script>
        // Counter animation for statistics
        document.addEventListener('DOMContentLoaded', function() {
            const counters = document.querySelectorAll('.counter');
            const speed = 200;

            counters.forEach(counter => {
                const animate = () => {
                    const value = +counter.getAttribute('data-target');
                    const data = +counter.innerText;
                    const time = value / speed;

                    if (data < value) {
                        counter.innerText = Math.ceil(data + time);
                        setTimeout(animate, 1);
                    } else {
                        counter.innerText = value;
                    }
                };
                animate();
            });
        });

        // Intersection Observer for fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });
    </script>
@endsection