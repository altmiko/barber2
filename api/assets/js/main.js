(function() {
  'use strict';

  // DOM Elements
  const header = document.querySelector('.header');
  const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
  const mobileMenu = document.querySelector('.mobile-menu');
  const testimonialSlider = document.querySelector('.testimonials-slider');
  const flashMessages = document.querySelectorAll('.alert');

  // Header scroll effect
  window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });

  // Mobile menu toggle
  if (mobileMenuToggle) {
    mobileMenuToggle.addEventListener('click', () => {
      mobileMenu.classList.toggle('active');
      document.body.classList.toggle('menu-open');
      
      // Toggle hamburger animation
      const spans = mobileMenuToggle.querySelectorAll('span');
      spans.forEach(span => span.classList.toggle('active'));
    });
  }

  // Testimonial Auto Scroll
  let testimonialScrollInterval;
  
  if (testimonialSlider && testimonialSlider.children.length > 2) {
    const startAutoScroll = () => {
      testimonialScrollInterval = setInterval(() => {
        testimonialSlider.scrollBy({ 
          left: 320, 
          behavior: 'smooth' 
        });
        
        // Reset to beginning when reached the end
        if (testimonialSlider.scrollLeft + testimonialSlider.clientWidth >= testimonialSlider.scrollWidth - 50) {
          setTimeout(() => {
            testimonialSlider.scrollTo({ left: 0, behavior: 'smooth' });
          }, 1000);
        }
      }, 4000);
    };
    
    startAutoScroll();
    
    // Pause scroll on hover or touch
    testimonialSlider.addEventListener('mouseenter', () => {
      clearInterval(testimonialScrollInterval);
    });
    
    testimonialSlider.addEventListener('mouseleave', () => {
      startAutoScroll();
    });
    
    testimonialSlider.addEventListener('touchstart', () => {
      clearInterval(testimonialScrollInterval);
    }, { passive: true });
    
    testimonialSlider.addEventListener('touchend', () => {
      startAutoScroll();
    }, { passive: true });
  }

  // Auto hide flash messages
  if (flashMessages.length > 0) {
    flashMessages.forEach(message => {
      setTimeout(() => {
        message.style.opacity = '0';
        setTimeout(() => {
          message.style.display = 'none';
        }, 500);
      }, 5000);
    });
  }

  // Form validation
  const forms = document.querySelectorAll('form[data-validate="true"]');
  
  if (forms.length > 0) {
    forms.forEach(form => {
      form.addEventListener('submit', function(e) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
          if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
            
            // Create or show error message
            let errorMessage = field.nextElementSibling;
            if (!errorMessage || !errorMessage.classList.contains('error-message')) {
              errorMessage = document.createElement('div');
              errorMessage.className = 'error-message';
              field.parentNode.insertBefore(errorMessage, field.nextSibling);
            }
            errorMessage.textContent = `${field.getAttribute('data-label') || 'This field'} is required`;
            errorMessage.style.display = 'block';
          } else {
            field.classList.remove('is-invalid');
            const errorMessage = field.nextElementSibling;
            if (errorMessage && errorMessage.classList.contains('error-message')) {
              errorMessage.style.display = 'none';
            }
          }
        });

        // Email validation
        const emailFields = form.querySelectorAll('input[type="email"]');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        emailFields.forEach(field => {
          if (field.value.trim() && !emailRegex.test(field.value.trim())) {
            isValid = false;
            field.classList.add('is-invalid');
            
            // Create or show error message
            let errorMessage = field.nextElementSibling;
            if (!errorMessage || !errorMessage.classList.contains('error-message')) {
              errorMessage = document.createElement('div');
              errorMessage.className = 'error-message';
              field.parentNode.insertBefore(errorMessage, field.nextSibling);
            }
            errorMessage.textContent = 'Please enter a valid email address';
            errorMessage.style.display = 'block';
          }
        });

        // Phone validation
        const phoneFields = form.querySelectorAll('input[type="tel"]');
        const phoneRegex = /^\d{10,11}$/;
        
        phoneFields.forEach(field => {
          if (field.value.trim() && !phoneRegex.test(field.value.replace(/[^0-9]/g, ''))) {
            isValid = false;
            field.classList.add('is-invalid');
            
            // Create or show error message
            let errorMessage = field.nextElementSibling;
            if (!errorMessage || !errorMessage.classList.contains('error-message')) {
              errorMessage = document.createElement('div');
              errorMessage.className = 'error-message';
              field.parentNode.insertBefore(errorMessage, field.nextSibling);
            }
            errorMessage.textContent = 'Please enter a valid phone number (10-11 digits)';
            errorMessage.style.display = 'block';
          }
        });

        if (!isValid) {
          e.preventDefault();
        }
      });
      
      // Real-time validation feedback
      const fields = form.querySelectorAll('input, select, textarea');
      fields.forEach(field => {
        field.addEventListener('input', function() {
          if (field.hasAttribute('required') && !field.value.trim()) {
            field.classList.add('is-invalid');
          } else {
            field.classList.remove('is-invalid');
            const errorMessage = field.nextElementSibling;
            if (errorMessage && errorMessage.classList.contains('error-message')) {
              errorMessage.style.display = 'none';
            }
          }
        });
      });
    });
  }

  // Date and time picker enhancements for booking
  const dateInputs = document.querySelectorAll('.date-select');
  if (dateInputs.length > 0) {
    dateInputs.forEach(input => {
      // Set min date to today
      const today = new Date();
      const dd = String(today.getDate()).padStart(2, '0');
      const mm = String(today.getMonth() + 1).padStart(2, '0');
      const yyyy = today.getFullYear();
      input.min = yyyy + '-' + mm + '-' + dd;
      
      // Set max date to 3 months from now
      const maxDate = new Date();
      maxDate.setMonth(maxDate.getMonth() + 3);
      const maxDd = String(maxDate.getDate()).padStart(2, '0');
      const maxMm = String(maxDate.getMonth() + 1).padStart(2, '0');
      const maxYyyy = maxDate.getFullYear();
      input.max = maxYyyy + '-' + maxMm + '-' + maxDd;
    });
  }

  // Dynamic service selection
  const serviceSelects = document.querySelectorAll('.service-select');
  if (serviceSelects.length > 0) {
    serviceSelects.forEach(select => {
      select.addEventListener('change', function() {
        const servicePrice = select.options[select.selectedIndex].getAttribute('data-price');
        const serviceDuration = select.options[select.selectedIndex].getAttribute('data-duration');
        const priceDisplay = document.getElementById('service-price');
        const durationDisplay = document.getElementById('service-duration');
        
        if (priceDisplay) {
          priceDisplay.textContent = servicePrice ? `$${servicePrice}` : '-';
        }
        
        if (durationDisplay) {
          durationDisplay.textContent = serviceDuration ? `${serviceDuration} min` : '-';
        }
      });
    });
  }

  // Rating system for reviews
  const ratingInputs = document.querySelectorAll('.rating-input');
  const ratingStars = document.querySelectorAll('.rating-star');
  
  if (ratingStars.length > 0) {
    ratingStars.forEach((star, index) => {
      star.addEventListener('click', () => {
        const ratingValue = index + 1;
        
        // Update hidden input value
        if (ratingInputs.length > 0) {
          ratingInputs[0].value = ratingValue;
        }
        
        // Update star display
        ratingStars.forEach((s, i) => {
          if (i < ratingValue) {
            s.classList.add('active');
            s.querySelector('i').className = 'fas fa-star';
          } else {
            s.classList.remove('active');
            s.querySelector('i').className = 'far fa-star';
          }
        });
      });
      
      // Hover effect
      star.addEventListener('mouseenter', () => {
        const ratingValue = index + 1;
        
        ratingStars.forEach((s, i) => {
          if (i < ratingValue) {
            s.querySelector('i').className = 'fas fa-star';
          } else {
            s.querySelector('i').className = 'far fa-star';
          }
        });
      });
      
      // Reset on mouse leave
      star.addEventListener('mouseleave', () => {
        const currentRating = ratingInputs.length > 0 ? parseInt(ratingInputs[0].value || 0) : 0;
        
        ratingStars.forEach((s, i) => {
          if (i < currentRating) {
            s.querySelector('i').className = 'fas fa-star';
          } else {
            s.querySelector('i').className = 'far fa-star';
          }
        });
      });
    });
  }
})();