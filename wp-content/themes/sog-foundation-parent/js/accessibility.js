// Custom JavaScript for accessibility enhancements. This file is enqueued in functions.php and will be loaded on the front end of the site. You can add any custom JavaScript code you need for accessibility purposes here.

// Add alt attributes to all images that do not have one already. This is for accessibility purposes.
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img:not([alt])');
    images.forEach(function(img) {
        img.setAttribute('alt', 'Image');
    });
});

// Add aria-label to all links that do not have one already. This is for accessibility purposes.
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a:not([aria-label])');
    links.forEach(function(link) {
        link.setAttribute('aria-label', 'Link');
    });
});

// Add aria-label to all buttons that do not have one already. This is for accessibility purposes.
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('button:not([aria-label])');
    buttons.forEach(function(button) {
        button.setAttribute('aria-label', 'Button');
    });
});

// Add aria-label to all form controls that do not have one already. This is for accessibility purposes.
document.addEventListener('DOMContentLoaded', function() {
    const formControls = document.querySelectorAll('input:not([aria-label]), select:not([aria-label]), textarea:not([aria-label])');
    formControls.forEach(function(control) {
        control.setAttribute('aria-label', 'Form Control');
    });
});

// Add aria-label to all nav elements that do not have one already. This is for accessibility purposes.
document.addEventListener('DOMContentLoaded', function() {
    const navs = document.querySelectorAll('nav:not([aria-label])');
    navs.forEach(function(nav) {
        nav.setAttribute('aria-label', 'Navigation');
    });
});

// Add title attribute to all links that do not have one already. This is for accessibility purposes.
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a:not([title])');
    links.forEach(function(link) {
        link.setAttribute('title', 'Link');
    });
});

// Add a light theme mode toggle for users who prefer a light theme.
// This button allows users to switch to a light color scheme for better accessibility.
document.addEventListener('DOMContentLoaded', function() {
    // Create the toggle button for light theme
    const toggleButton = document.createElement('button');
    toggleButton.innerHTML = '<i class="fa-regular fa-sun" aria-hidden="true"></i> Light Theme';
    toggleButton.setAttribute('aria-label', 'Toggle Light Theme'); // Accessibility: describes the button's action
    // Position the button in the bottom-right corner
    toggleButton.style.position = 'fixed';
    toggleButton.style.bottom = '10px';
    toggleButton.style.right = '10px';
    toggleButton.style.zIndex = '1000';
    document.body.appendChild(toggleButton);

    // Toggle the 'light-theme' class on the body when clicked
    toggleButton.addEventListener('click', function() {
        if (document.body.classList.contains('dark-theme')) {
            document.body.classList.remove('dark-theme');
        }
        document.body.classList.toggle('light-theme');
    });
});

// Add a dark theme mode toggle for users who prefer a dark theme.
// This button allows users to switch to a dark color scheme for better accessibility and reduced eye strain.
document.addEventListener('DOMContentLoaded', function() {
    // Create the toggle button for dark theme
    const toggleButton = document.createElement('button');
    toggleButton.innerHTML = '<i class="fa-solid fa-moon" aria-hidden="true"></i> Dark Theme';
    toggleButton.setAttribute('aria-label', 'Toggle Dark Theme'); // Accessibility: describes the button's action
    // Position the button above the light theme toggle
    toggleButton.style.position = 'fixed';
    toggleButton.style.bottom = '50px';
    toggleButton.style.right = '10px';
    toggleButton.style.zIndex = '1000';
    document.body.appendChild(toggleButton);

    // Toggle the 'dark-theme' class on the body when clicked
    toggleButton.addEventListener('click', function() {
        if (document.body.classList.contains('light-theme')) {
            document.body.classList.remove('light-theme');
        }
        document.body.classList.toggle('dark-theme');
    });
});
