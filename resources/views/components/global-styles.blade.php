<!-- Global Styles for All Pages -->
<style>
  /* Prevent overscroll on all pages */
  html, body {
    overscroll-behavior: none;
  }

  /* Card Hover Effects */
  .card-hover {
    transition: all 0.3s ease-in-out;
  }

  .card-hover:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
  }

  /* See More Card Effects */
  .see-more-card {
    transition: all 0.3s ease;
    cursor: pointer;
  }

  .see-more-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    background-color: rgba(var(--color-primary-500), 0.05);
  }
</style>
