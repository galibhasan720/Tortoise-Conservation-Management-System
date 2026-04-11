<?php
/**
 * JavaScript Footer Template - Common for all pages
 * This includes Bootstrap and AOS library initialization
 * 
 * @author Senior Full-Stack Developer
 */
?>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS (Animate On Scroll) Library -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2/dist/aos.js"></script>
    <script>
        // Initialize AOS for smooth scroll animations
        AOS.init({
            duration: 600,           // animation duration in milliseconds
            easing: 'ease-out',      // easing function
            once: false,             // animation plays every time element is in viewport
            mirror: true,            // animations play while scrolling down and up
            offset: 100              // trigger animation 100px before element enters viewport
        });
    </script>
