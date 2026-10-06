/**
 * LPM SCU - Main JavaScript
 * Handles interactions, animations, and UI states.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Progress Bar Animation
    const progressBars = document.querySelectorAll('.hero-progress-fill');
    if (progressBars.length > 0) {
        // Delay animation slightly for better effect on load
        setTimeout(() => {
            progressBars.forEach(bar => {
                const target = bar.getAttribute('data-target');
                if (target) {
                    bar.style.width = target + '%';
                }
            });
        }, 300);
    }

    // 2. Stat Counter Animation
    const stats = document.querySelectorAll('.stat-num');
    if (stats.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    const countTo = parseInt(target.getAttribute('data-count'), 10);
                    
                    if (!isNaN(countTo) && !target.classList.contains('counted')) {
                        let currentCount = 0;
                        const duration = 2000; // 2 seconds
                        const increment = Math.max(1, Math.floor(countTo / (duration / 16))); // 16ms per frame (60fps)
                        
                        const updateCounter = () => {
                            currentCount += increment;
                            if (currentCount > countTo) currentCount = countTo;
                            
                            // Keep the span if it exists
                            const span = target.querySelector('span');
                            target.innerHTML = currentCount + (span ? span.outerHTML : '');
                            
                            if (currentCount < countTo) {
                                requestAnimationFrame(updateCounter);
                            } else {
                                target.classList.add('counted');
                            }
                        };
                        
                        updateCounter();
                    }
                }
            });
        }, { threshold: 0.5 });
        
        stats.forEach(stat => observer.observe(stat));
    }

    // 3. Navbar Sticky Shadow Effect (Keeps background solid, only toggles shadow)
    const navbar = document.getElementById('main-navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.classList.add('navbar-scrolled');
            } else {
                navbar.classList.remove('navbar-scrolled');
            }
        });
    }

    // 4. File Upload Preview (Admin)
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file_doc');
    const fileInfo = document.getElementById('file-info');

    if (dropZone && fileInput) {
        // Click to open file dialog
        dropZone.addEventListener('click', () => {
            fileInput.click();
        });

        // Drag and drop events
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--purple)';
            dropZone.style.background = 'rgba(106, 27, 154, 0.05)';
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--border)';
            dropZone.style.background = 'transparent';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = 'var(--purple)';
            dropZone.style.background = 'transparent';
            
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                updateFileInfo(fileInput.files[0]);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                updateFileInfo(fileInput.files[0]);
            }
        });

        function updateFileInfo(file) {
            if (fileInfo) {
                const sizeKB = (file.size / 1024).toFixed(1);
                fileInfo.innerHTML = `<strong>✓ File dipilih:</strong> ${file.name} (${sizeKB} KB)`;
                fileInfo.style.color = 'var(--purple)';
            }
        }
    }

    // 5. Desktop Navbar Dropdown Mutual Exclusivity & Click Navigation
    const navHoverDropdowns = document.querySelectorAll('#main-navbar .nav-hover-dropdown');
    if (navHoverDropdowns.length > 0) {
        navHoverDropdowns.forEach(item => {
            const toggleLink = item.querySelector(':scope > a.nav-link');

            // Allow clicking top-level nav links on desktop to navigate to the page
            if (toggleLink) {
                toggleLink.addEventListener('click', (e) => {
                    if (window.innerWidth >= 1200) {
                        const href = toggleLink.getAttribute('href');
                        if (href && href !== '#' && !href.startsWith('javascript:')) {
                            window.location.href = href;
                        }
                    }
                });
            }

            item.addEventListener('mouseenter', () => {
                if (window.innerWidth >= 1200) {
                    navHoverDropdowns.forEach(sibling => {
                        if (sibling !== item) {
                            sibling.classList.remove('show');
                            const toggle = sibling.querySelector('.dropdown-toggle');
                            if (toggle) {
                                toggle.classList.remove('show');
                                toggle.setAttribute('aria-expanded', 'false');
                            }
                            const menu = sibling.querySelector('.dropdown-menu');
                            if (menu) {
                                menu.classList.remove('show');
                                menu.style.display = 'none';
                                setTimeout(() => {
                                    menu.style.display = '';
                                }, 60);
                            }
                        }
                    });
                }
            });
        });
    }
});

