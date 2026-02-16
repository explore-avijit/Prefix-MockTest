document.addEventListener('DOMContentLoaded', () => {
    // --- Elements ---
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const navbar = document.getElementById('navbar');
    const navContainer = document.getElementById('nav-container');
    const mobileDropdownBtns = document.querySelectorAll('.mobile-dropdown-btn');

    // --- Scroll Effect ---
    // --- Scroll Effect ---
    function handleScroll() {
        const isScrolled = window.scrollY > 10;

        if (isScrolled) {
            // Scrolled: Compact, High Blur, Subtle Shadow
            navbar.classList.add(
                'bg-white/80', 'dark:bg-slate-950/80',
                'backdrop-blur-lg',
                'shadow-sm',
                'border-b', 'border-slate-200/60', 'dark:border-slate-800/60'
            );
            navbar.classList.remove('border-transparent');

            // Interaction: Compact Height
            navContainer.classList.remove('h-20');
            navContainer.classList.add('h-16');
        } else {
            // Top: Transparent, Spacious
            navbar.classList.remove(
                'bg-white/80', 'dark:bg-slate-950/80',
                'backdrop-blur-lg',
                'shadow-sm',
                'border-b', 'border-slate-200/60', 'dark:border-slate-800/60'
            );
            navbar.classList.add('border-transparent');

            // Interaction: Expanded Height
            navContainer.classList.remove('h-16');
            navContainer.classList.add('h-20');
        }

        // Cleanup any previous floating classes
        navbar.classList.remove('md:top-4', 'md:left-4', 'md:right-4', 'md:w-auto', 'md:rounded-2xl', 'md:border');
    }

    window.addEventListener('scroll', handleScroll);
    handleScroll(); // Init check

    // --- Mobile Menu Toggle ---
    // --- Mobile Menu Toggle ---
    // --- Mobile Menu Toggle ---
    const toggleMobileMenu = () => {
        const isClosed = mobileMenu.classList.contains('invisible');
        mobileMenuBtn.classList.toggle('active'); // Toggle Animation State
        
        const animateItems = mobileMenu.querySelectorAll('.mobile-item-animate');

        if (isClosed) {
            // Open
            mobileMenu.classList.remove('invisible', 'opacity-0', '-translate-y-4');
            document.body.style.overflow = 'hidden'; // Lock Body Scroll

            // Staggered Animation In
            if (animateItems.length > 0) {
                 animateItems.forEach((item, index) => {
                    setTimeout(() => {
                        item.classList.remove('opacity-0', 'translate-y-4');
                    }, 50 + (index * 60)); // 60ms stagger delay
                });
            }

        } else {
            // Close Sequence
            document.body.style.overflow = ''; // Unlock Body Scroll
            
            // Close (Instant/CSS Transition Only)
            document.body.style.overflow = ''; // Unlock Body Scroll
            mobileMenu.classList.add('invisible', 'opacity-0', '-translate-y-4');
            
            // Reset Animation State (Instant) so they are ready for next entry
            animateItems.forEach(item => {
                item.classList.add('opacity-0', 'translate-y-4');
            });

            // Reset/Collapse all dropdowns
            const openDropdowns = mobileMenu.querySelectorAll('.mobile-dropdown-content');
            const rotatedArrows = mobileMenu.querySelectorAll('.mobile-dropdown-btn svg');
            
            openDropdowns.forEach(dropdown => {
                dropdown.classList.remove('grid-rows-[1fr]');
                dropdown.classList.add('grid-rows-[0fr]');
            });
            rotatedArrows.forEach(arrow => {
                arrow.classList.remove('rotate-180');
            });
        }
    };

    mobileMenuBtn.addEventListener('click', toggleMobileMenu);

    // --- Auto-Close Mobile Menu on Link Click ---
    const mobileLinks = mobileMenu.querySelectorAll('a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            // Close menu when a link is clicked
            if (!mobileMenu.classList.contains('invisible')) {
                toggleMobileMenu();
            }
        });
    });

    // --- Mobile Dropdowns (Exclusive Accordion with Smooth Grid Animation) ---
    mobileDropdownBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            // Find the wrapper (it's the next sibling now)
            const contentWrapper = btn.nextElementSibling; 
            const arrow = btn.querySelector('svg');
            
            // Close ALL other open dropdowns first
            mobileDropdownBtns.forEach(otherBtn => {
                if (otherBtn !== btn) {
                    const otherWrapper = otherBtn.nextElementSibling;
                    const otherArrow = otherBtn.querySelector('svg');
                    
                    // Force close others
                    if (otherWrapper && otherWrapper.classList.contains('grid-rows-[1fr]')) {
                        otherWrapper.classList.remove('grid-rows-[1fr]');
                        otherWrapper.classList.add('grid-rows-[0fr]'); // Explicitly set closed
                        if (otherArrow) otherArrow.classList.remove('rotate-180');
                    }
                }
            });

            // Toggle current
            if (contentWrapper) {
                if (contentWrapper.classList.contains('grid-rows-[1fr]')) {
                    // Close
                    contentWrapper.classList.remove('grid-rows-[1fr]');
                    contentWrapper.classList.add('grid-rows-[0fr]');
                    if (arrow) arrow.classList.remove('rotate-180');
                } else {
                    // Open
                    contentWrapper.classList.remove('grid-rows-[0fr]');
                    contentWrapper.classList.add('grid-rows-[1fr]');
                    if (arrow) arrow.classList.add('rotate-180');
                }
            }
        });
    });

    // --- Close Mobile Menu on Resize ---
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 768) {
            mobileMenu.classList.add('invisible', 'opacity-0', '-translate-y-4');
        }
    });

    // --- Dark Mode Logic ---
    const themeToggleBtn = document.getElementById('theme-toggle');
    const mobileThemeToggleBtn = document.getElementById('mobile-theme-toggle');
    const darkIcon = document.getElementById('theme-toggle-dark-icon');
    const lightIcon = document.getElementById('theme-toggle-light-icon');

    // Check preference
    if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
        lightIcon.classList.remove('hidden');
    } else {
        document.documentElement.classList.remove('dark');
        darkIcon.classList.remove('hidden');
    }

    function toggleTheme() {
        // Toggle icons
        darkIcon.classList.toggle('hidden');
        lightIcon.classList.toggle('hidden');

        // Toggle class
        if (localStorage.getItem('color-theme')) {
            if (localStorage.getItem('color-theme') === 'light') {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            } else {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            }
        } else {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        }
    }

    themeToggleBtn.addEventListener('click', toggleTheme);
    mobileThemeToggleBtn.addEventListener('click', toggleTheme);

    // --- Experts Carousel Logic ---
    const expertCarousel = document.getElementById('experts-carousel');
    const expertPrev = document.getElementById('expert-prev');
    const expertNext = document.getElementById('expert-next');
    const expertDotsContainer = document.getElementById('expert-dots');

    if (expertCarousel && expertPrev && expertNext) {

        // --- 1. Clone Cards for Infinite Effect ---
        const originalCards = Array.from(expertCarousel.querySelectorAll('.snap-center'));
        originalCards.forEach(card => {
            const clone = card.cloneNode(true);
            clone.setAttribute('aria-hidden', 'true'); // Hide from screen readers
            expertCarousel.appendChild(clone);
        });

        const getCardWidth = () => {
            const firstCard = expertCarousel.querySelector('.snap-center');
            // gap-8 = 32px
            return firstCard ? firstCard.offsetWidth + 32 : 360;
        };

        const totalOriginal = originalCards.length;

        const scrollToCard = (index) => {
            expertCarousel.scrollTo({
                left: index * getCardWidth(),
                behavior: 'smooth'
            });
            return index; // Return target for checking
        };

        let isScrolling = false;

        const handleInfiniteScroll = () => {
            if (isScrolling) return;

            const cardWidth = getCardWidth();
            const scrollLeft = expertCarousel.scrollLeft;
            const currentIndex = Math.round(scrollLeft / cardWidth);

            // If we have scrolled into the cloned set (beyond original set), reset instantly
            if (currentIndex >= totalOriginal) {
                isScrolling = true;
                // Calculate the equivalent position in the first set
                const resetIndex = currentIndex - totalOriginal;

                // Disable scroll snap momentarily to prevent glitch
                expertCarousel.style.scrollBehavior = 'auto';
                expertCarousel.scrollLeft = resetIndex * cardWidth;
                expertCarousel.style.scrollBehavior = 'smooth';

                isScrolling = false;
            }
        };

        // Use scrollend or debounce to detect end of scroll for resetting
        let scrollTimeout;
        expertCarousel.addEventListener('scroll', () => {
            requestAnimationFrame(updateState);

            clearTimeout(scrollTimeout);
            scrollTimeout = setTimeout(() => {
                // If we are at the EXACT snap point of a clone, reset to original
                const cardWidth = getCardWidth();
                const currentIndex = Math.round(expertCarousel.scrollLeft / cardWidth);

                if (currentIndex >= totalOriginal) {
                    expertCarousel.style.scrollBehavior = 'auto';
                    expertCarousel.scrollLeft = (currentIndex % totalOriginal) * cardWidth;
                    expertCarousel.style.scrollBehavior = 'smooth';
                }
            }, 500); // Wait for smooth scroll to finish
        });


        const updateState = () => {
            const scrollLeft = expertCarousel.scrollLeft;
            const cardWidth = getCardWidth();
            const activeIndex = Math.round(scrollLeft / cardWidth);
            const realIndex = activeIndex % totalOriginal; // Map clone index to real index

            // Always Enable Buttons
            expertPrev.disabled = false;
            expertNext.disabled = false;
            expertPrev.style.opacity = '1';
            expertNext.style.opacity = '1';

            // Update Dots
            if (expertDotsContainer) {
                Array.from(expertDotsContainer.children).forEach((dot, index) => {
                    if (index === realIndex) {
                        dot.className = 'w-8 h-2.5 rounded-full transition-all duration-300 bg-indigo-600';
                        dot.style.width = '2rem';
                    } else {
                        dot.className = 'w-2.5 h-2.5 rounded-full transition-all duration-300 bg-slate-300 dark:bg-slate-700 hover:bg-slate-400';
                        dot.style.width = '0.625rem';
                    }
                });
            }
        };

        // Active Teleport logic for seamless Next click
        expertNext.addEventListener('click', () => {
            const cardWidth = getCardWidth();
            let current = Math.round(expertCarousel.scrollLeft / cardWidth);

            // If we are deep in the clones (or at the first clone), effectively at the "end"
            if (current >= totalOriginal) {
                // 1. Teleport back to the real equivalent instantly
                const realIndex = current % totalOriginal;
                expertCarousel.style.scrollBehavior = 'auto';
                expertCarousel.scrollLeft = realIndex * cardWidth;
                expertCarousel.style.scrollBehavior = 'smooth';

                // 2. Update current to the new real index
                current = realIndex;

                // 3. Force a tiny layout reflow/paint to ensure the browser accepts the jump before smooth scrolling
                // Using requestAnimationFrame ensures the next scroll happens in the next frame
                requestAnimationFrame(() => {
                    scrollToCard(current + 1);
                });
            } else {
                scrollToCard(current + 1);
            }
        });

        expertPrev.addEventListener('click', () => {
            const current = Math.round(expertCarousel.scrollLeft / getCardWidth());
            // If at 0 and going back, jump to end of real set first (to clones)
            if (current === 0) {
                expertCarousel.style.scrollBehavior = 'auto';
                expertCarousel.scrollLeft = totalOriginal * getCardWidth(); // Jump to same card in clone set
                expertCarousel.style.scrollBehavior = 'smooth';

                requestAnimationFrame(() => {
                    scrollToCard(totalOriginal - 1);
                });
            } else {
                scrollToCard(current - 1);
            }
        });

        // --- Dots Generation (Real cards only) ---
        if (expertDotsContainer) {
            expertDotsContainer.innerHTML = '';
            originalCards.forEach((card, index) => {
                const dot = document.createElement('button');
                dot.className = `w-2.5 h-2.5 rounded-full transition-all duration-300 bg-slate-300 dark:bg-slate-700 hover:bg-slate-400`;
                dot.ariaLabel = `Go to slide ${index + 1}`;
                dot.addEventListener('click', () => {
                    scrollToCard(index); // Always scroll to the primary set
                });
                expertDotsContainer.appendChild(dot);
            });
            updateState();
        }

        setTimeout(updateState, 100);
    }
});
