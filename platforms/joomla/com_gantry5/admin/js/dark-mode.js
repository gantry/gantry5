// Gantry5 Dark Mode for Joomla
document.addEventListener('DOMContentLoaded', function() {
    // Check if Joomla is using dark mode
    const htmlElement = document.documentElement;
    const bodyElement = document.body;
    
    // Various ways to detect dark mode in Joomla
    const isJoomlaDarkMode = 
        htmlElement.getAttribute('data-color-scheme') === 'dark' || 
        htmlElement.getAttribute('data-bs-theme') === 'dark' ||
        htmlElement.classList.contains('dark-mode') ||
        bodyElement.classList.contains('dark-mode') ||
        window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    console.log('Gantry5 Dark Mode: Joomla dark mode detected:', isJoomlaDarkMode);
    
    // Apply dark mode class to Gantry container
    if (isJoomlaDarkMode) {
        const gantryContainer = document.getElementById('g5-container');
        if (gantryContainer) {
            gantryContainer.classList.add('g5-dark-mode');
            console.log('Gantry5 Dark Mode: Applied dark mode to Gantry container');
        }
    }
    
    // Watch for changes in Joomla's color scheme
    const observeHtml = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' || mutation.type === 'classList') {
                const isDark = 
                    htmlElement.getAttribute('data-color-scheme') === 'dark' || 
                    htmlElement.getAttribute('data-bs-theme') === 'dark' ||
                    htmlElement.classList.contains('dark-mode') ||
                    bodyElement.classList.contains('dark-mode');
                
                const gantryContainer = document.getElementById('g5-container');
                if (gantryContainer) {
                    if (isDark) {
                        gantryContainer.classList.add('g5-dark-mode');
                        console.log('Gantry5 Dark Mode: Applied dark mode to Gantry container (mutation)');
                    } else {
                        gantryContainer.classList.remove('g5-dark-mode');
                        console.log('Gantry5 Dark Mode: Removed dark mode from Gantry container (mutation)');
                    }
                }
            }
        });
    });
    
    // Watch for system color scheme changes
    if (window.matchMedia) {
        const colorSchemeMedia = window.matchMedia('(prefers-color-scheme: dark)');
        colorSchemeMedia.addEventListener('change', function(e) {
            const isDark = e.matches;
            const gantryContainer = document.getElementById('g5-container');
            if (gantryContainer) {
                if (isDark) {
                    gantryContainer.classList.add('g5-dark-mode');
                    console.log('Gantry5 Dark Mode: Applied dark mode to Gantry container (media)');
                } else {
                    gantryContainer.classList.remove('g5-dark-mode');
                    console.log('Gantry5 Dark Mode: Removed dark mode from Gantry container (media)');
                }
            }
        });
    }
    
    // Start observing HTML element for attribute and class changes
    observeHtml.observe(htmlElement, { 
        attributes: true, 
        attributeFilter: ['data-color-scheme', 'data-bs-theme', 'class'] 
    });
    
    // Also observe body element for class changes
    observeHtml.observe(bodyElement, { 
        attributes: true, 
        attributeFilter: ['class'] 
    });
    
    // Force apply dark mode if needed
    const forceApply = function() {
        if (isJoomlaDarkMode) {
            const gantryContainer = document.getElementById('g5-container');
            if (gantryContainer) {
                gantryContainer.classList.add('g5-dark-mode');
                console.log('Gantry5 Dark Mode: Force applied dark mode to Gantry container');
            }
        }
    };
    
    // Try to apply dark mode after a short delay (in case container is loaded later)
    setTimeout(forceApply, 500);
});