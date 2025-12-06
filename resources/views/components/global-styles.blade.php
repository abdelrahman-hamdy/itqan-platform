<!-- Global Styles for All Pages -->
<style>
  /* Prevent overscroll on all pages */
  html, body {
    overscroll-behavior: none;
  }

  /* Card Hover Effects - Using Tailwind-compatible approach */
  .card-hover {
    @apply transition-all duration-300 ease-in-out;
  }

  .card-hover:hover {
    @apply -translate-y-1 shadow-xl;
  }

  /* See More Card Effects */
  .see-more-card {
    @apply transition-all duration-300 cursor-pointer;
  }

  .see-more-card:hover {
    @apply -translate-y-1 shadow-xl bg-primary/5;
  }
</style>
