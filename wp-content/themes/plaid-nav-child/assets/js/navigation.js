/**
 * Plaid Navigation JavaScript
 * Exact replication of Plaid.com navigation behavior
 *
 * @package PlaidNavChild
 * @version 2.0.0
 */

(function() {
	'use strict';

	/**
	 * Plaid Navigation Controller
	 * Based on comprehensive analysis of plaid.com navigation behavior
	 */
	const PlaidNavigation = {
		// Configuration extracted from Plaid.com
		config: {
			// Timing values
			hoverDelay: 150,
			closeDelay: 300,
			animationDuration: 200,

			// Breakpoints
			desktopBreakpoint: 768,

			// Selectors matching Plaid.com structure
			selectors: {
				header: '.plaid-header',
				container: '.plaid-header-container',
				desktopNav: '.plaid-nav-desktop',
				mobileNav: '.plaid-nav-mobile',
				mobileToggle: '.plaid-mobile-toggle',
				mobileOverlay: '.plaid-mobile-overlay',
				mobileBackdrop: '.plaid-mobile-backdrop',
				navList: '.plaid-nav-list',
				navItem: '.plaid-nav-item',
				navLink: '.plaid-nav-link',
				navArrow: '.plaid-nav-arrow',
				dropdown: '.plaid-dropdown',
				megaMenu: '.plaid-mega-menu',
				mobileToggleBtn: '.plaid-mobile-toggle-btn',
				mobileSubmenu: '.plaid-mobile-submenu'
			}
		},

		// State management
		state: {
			currentOpenMenu: null,
			hoverTimer: null,
			closeTimer: null,
			isMobileOpen: false,
			scrollY: 0,
			isScrolled: false
		},

		/**
		 * Initialize the navigation system
		 */
		init() {
			this.cacheElements();
			this.bindEvents();
			this.initScrollEffects();
			this.initKeyboardNavigation();
			this.initAccessibility();
			this.handleResize();
		},

		/**
		 * Cache DOM elements
		 */
		cacheElements() {
			this.header = document.querySelector(this.config.selectors.header);
			this.desktopNav = document.querySelector(this.config.selectors.desktopNav);
			this.mobileNav = document.querySelector(this.config.selectors.mobileNav);
			this.mobileToggle = document.querySelector(this.config.selectors.mobileToggle);
			this.mobileOverlay = document.querySelector(this.config.selectors.mobileOverlay);
			this.mobileBackdrop = document.querySelector(this.config.selectors.mobileBackdrop);
			this.navItems = document.querySelectorAll(this.config.selectors.navItem);
			this.mobileToggleBtns = document.querySelectorAll(this.config.selectors.mobileToggleBtn);
		},

		/**
		 * Bind all event listeners
		 */
		bindEvents() {
			this.bindDesktopEvents();
			this.bindMobileEvents();
			this.bindScrollEvents();
			this.bindResizeEvents();
		},

		/**
		 * Desktop navigation events
		 */
		bindDesktopEvents() {
			if (!this.desktopNav) return;

			this.navItems.forEach(item => {
				const link = item.querySelector(this.config.selectors.navLink);
				const dropdown = item.querySelector(this.config.selectors.dropdown);
				const megaMenu = item.querySelector(this.config.selectors.megaMenu);
				const menu = dropdown || megaMenu;

				if (!menu) return;

				// Click to toggle menu - CLICK ONLY (no hover)
				link.addEventListener('click', (e) => {
					e.preventDefault();
					e.stopPropagation(); // Prevent bubbling to document click handler
					this.toggleMenu(item);
				});

				// Keyboard navigation - focus opens menu (but not after click)
				link.addEventListener('focus', (e) => {
					// Only handle focus from keyboard tab, not from click
					if (e.relatedTarget !== null) {
						this.handleMenuFocus(item);
					}
				});
			});

			// Click outside to close
			document.addEventListener('click', (e) => {
				if (this.state.currentOpenMenu && !e.target.closest(this.config.selectors.desktopNav)) {
					this.closeMenu(this.state.currentOpenMenu);
				}
			});
		},

		/**
		 * Mobile navigation events
		 */
		bindMobileEvents() {
			if (!this.mobileToggle) return;

			// Toggle mobile menu
			this.mobileToggle.addEventListener('click', () => {
				this.toggleMobileMenu();
			});

			// Backdrop click to close
			if (this.mobileBackdrop) {
				this.mobileBackdrop.addEventListener('click', () => {
					this.closeMobileMenu();
				});
			}

			// Mobile submenu toggles
			this.mobileToggleBtns.forEach(btn => {
				btn.addEventListener('click', (e) => {
					this.toggleMobileSubmenu(e);
				});
			});

			// Escape key to close mobile menu
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && this.state.isMobileOpen) {
					this.closeMobileMenu();
					this.mobileToggle?.focus();
				}
			});
		},

		/**
		 * Scroll events
		 */
		bindScrollEvents() {
			let ticking = false;

			window.addEventListener('scroll', () => {
				if (!ticking) {
					window.requestAnimationFrame(() => {
						this.handleScroll();
						ticking = false;
					});
					ticking = true;
				}
			}, { passive: true });
		},

		/**
		 * Resize events
		 */
		bindResizeEvents() {
			let resizeTimer;
			window.addEventListener('resize', () => {
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(() => {
					this.handleResize();
				}, 250);
			});
		},

		/**
		 * Handle menu enter (hover)
		 */
		handleMenuEnter(e, item) {
			if (window.innerWidth < this.config.desktopBreakpoint) return;

			clearTimeout(this.state.closeTimer);

			this.state.hoverTimer = setTimeout(() => {
				this.openMenu(item);
			}, this.config.hoverDelay);
		},

		/**
		 * Handle menu leave (hover)
		 */
		handleMenuLeave(e, item) {
			if (window.innerWidth < this.config.desktopBreakpoint) return;

			clearTimeout(this.state.hoverTimer);
			this.startCloseTimer(item);
		},

		/**
		 * Handle menu focus
		 */
		handleMenuFocus(item) {
			if (window.innerWidth < this.config.desktopBreakpoint) return;

			if (this.state.currentOpenMenu && this.state.currentOpenMenu !== item) {
				this.closeMenu(this.state.currentOpenMenu);
			}

			this.openMenu(item);
		},

		/**
		 * Handle menu click (for touch devices)
		 */
		handleMenuClick(e, item) {
			const isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

			if (isTouch && window.innerWidth >= this.config.desktopBreakpoint) {
				const link = item.querySelector(this.config.selectors.navLink);
				const isExpanded = link.getAttribute('aria-expanded') === 'true';

				if (!isExpanded) {
					e.preventDefault();
					this.openMenu(item);
				}
			}
		},

		/**
		 * Open menu
		 */
		openMenu(item) {
			if (this.state.currentOpenMenu === item) return;


			if (this.state.currentOpenMenu) {
				this.closeMenu(this.state.currentOpenMenu);
			}

			const link = item.querySelector(this.config.selectors.navLink);
			const dropdown = item.querySelector(this.config.selectors.dropdown);
			const megaMenu = item.querySelector(this.config.selectors.megaMenu);
			const menu = dropdown || megaMenu;

			if (link && menu) {
				// Dynamic positioning based on header container
				const headerContainer = document.querySelector(this.config.selectors.container);
				if (headerContainer) {
					const rect = headerContainer.getBoundingClientRect();
					const topPosition = rect.bottom + 30; // Header bottom + 30px gap
					menu.style.top = topPosition + 'px';
				}

				link.setAttribute('aria-expanded', 'true');
				menu.classList.add('active');
				this.state.currentOpenMenu = item;
			}
		},

			/**
			 * Toggle menu (open/close) - Plaid.com click behavior
			 */
			toggleMenu(item) {
				if (this.state.currentOpenMenu === item) {
					// If already open, close it
					this.closeMenu(item);
				} else {
					// If different menu or none open, open this one
					if (this.state.currentOpenMenu) {
						this.closeMenu(this.state.currentOpenMenu);
					}
					this.openMenu(item);
				}
			},

		/**
		 * Close menu
		 */
		closeMenu(item) {
			const link = item.querySelector(this.config.selectors.navLink);
			const dropdown = item.querySelector(this.config.selectors.dropdown);
			const megaMenu = item.querySelector(this.config.selectors.megaMenu);
			const menu = dropdown || megaMenu;

			if (link && menu) {
				link.setAttribute('aria-expanded', 'false');
				menu.classList.remove('active');
			}

			if (this.state.currentOpenMenu === item) {
				this.state.currentOpenMenu = null;
			}
		},

		/**
		 * Start close timer
		 */
		startCloseTimer(item) {
			this.state.closeTimer = setTimeout(() => {
				this.closeMenu(item);
			}, this.config.closeDelay);
		},

		/**
		 * Toggle mobile menu
		 */
		toggleMobileMenu() {
			if (this.state.isMobileOpen) {
				this.closeMobileMenu();
			} else {
				this.openMobileMenu();
			}
		},

		/**
		 * Open mobile menu
		 */
		openMobileMenu() {
			this.mobileToggle?.setAttribute('aria-expanded', 'true');
			this.mobileOverlay?.classList.add('active');
			this.mobileBackdrop?.classList.add('active');
			this.header?.classList.add('mobile-menu-active');
			document.body.style.overflow = 'hidden';
			this.state.isMobileOpen = true;

			// Focus first menu item
			const firstLink = this.mobileOverlay?.querySelector('a');
			firstLink?.focus();
		},

		/**
		 * Close mobile menu
		 */
		closeMobileMenu() {
			this.mobileToggle?.setAttribute('aria-expanded', 'false');
				this.mobileOverlay?.classList.remove('active');
			this.mobileBackdrop?.classList.remove('active');
			this.header?.classList.remove('mobile-menu-active');
			document.body.style.overflow = '';
			this.state.isMobileOpen = false;

			this.closeAllMobileSubmenus();
		},

		/**
		 * Toggle mobile submenu
		 */
		toggleMobileSubmenu(e) {
			const btn = e.currentTarget;
			const targetId = btn.getAttribute('data-target');
			const submenu = document.getElementById(targetId);

			if (!submenu) return;

			const isExpanded = btn.getAttribute('aria-expanded') === 'true';

			if (isExpanded) {
				this.closeMobileSubmenu(btn, submenu);
			} else {
				this.openMobileSubmenu(btn, submenu);
			}
		},

		/**
		 * Open mobile submenu
		 */
		openMobileSubmenu(btn, submenu) {
			btn.setAttribute('aria-expanded', 'true');
			submenu.classList.add('active');

			const parentItem = btn.closest('.plaid-mobile-item');
			parentItem?.classList.add('submenu-open');
		},

		/**
		 * Close mobile submenu
		 */
		closeMobileSubmenu(btn, submenu) {
			btn.setAttribute('aria-expanded', 'false');
			submenu.classList.remove('active');

			const parentItem = btn.closest('.plaid-mobile-item');
			parentItem?.classList.remove('submenu-open');
		},

		/**
		 * Close all mobile submenus
		 */
		closeAllMobileSubmenus() {
			this.mobileToggleBtns.forEach(btn => {
				const targetId = btn.getAttribute('data-target');
				const submenu = document.getElementById(targetId);
				if (submenu && btn.getAttribute('aria-expanded') === 'true') {
					this.closeMobileSubmenu(btn, submenu);
				}
			});
		},

		/**
		 * Handle scroll
		 */
		handleScroll() {
			const scrollY = window.scrollY || window.pageYOffset;

			if (scrollY > 10 && !this.state.isScrolled) {
				this.header?.classList.add('scrolled');
				this.state.isScrolled = true;
			} else if (scrollY <= 10 && this.state.isScrolled) {
				this.header?.classList.remove('scrolled');
				this.state.isScrolled = false;
			}
		},

		/**
		 * Handle resize
		 */
		handleResize() {
			const isDesktop = window.innerWidth >= this.config.desktopBreakpoint;

			// Close mobile menu when switching to desktop
			if (isDesktop && this.state.isMobileOpen) {
				this.closeMobileMenu();
			}

			// Close all menus when switching breakpoints
			if (this.state.currentOpenMenu) {
				this.closeMenu(this.state.currentOpenMenu);
			}
		},

		/**
		 * Initialize scroll effects
		 */
		initScrollEffects() {
			this.handleScroll();
		},

		/**
		 * Initialize keyboard navigation
		 */
		initKeyboardNavigation() {
			document.addEventListener('keydown', (e) => {
				// Escape to close menus
				if (e.key === 'Escape') {
					if (this.state.currentOpenMenu) {
						this.closeMenu(this.state.currentOpenMenu);
					}
					if (this.state.isMobileOpen) {
						this.closeMobileMenu();
						this.mobileToggle?.focus();
					}
				}

				// Tab navigation in dropdowns
				if (e.key === 'Tab' && this.state.currentOpenMenu) {
					this.handleTabNavigation(e);
				}
			});
		},

		/**
		 * Handle tab navigation in open menu
		 */
		handleTabNavigation(e) {
			const currentMenu = this.state.currentOpenMenu;
			if (!currentMenu) return;

			const dropdown = currentMenu.querySelector(this.config.selectors.dropdown) ||
			                currentMenu.querySelector(this.config.selectors.megaMenu);

			if (!dropdown) return;

			const focusableElements = dropdown.querySelectorAll(
				'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
			);

			const firstElement = focusableElements[0];
			const lastElement = focusableElements[focusableElements.length - 1];

			if (e.shiftKey && document.activeElement === firstElement) {
				e.preventDefault();
				lastElement.focus();
			} else if (!e.shiftKey && document.activeElement === lastElement) {
				e.preventDefault();
				firstElement.focus();
			}
		},

		/**
		 * Initialize accessibility
		 */
		initAccessibility() {
			this.setupAriaAttributes();
		},

		/**
		 * Setup ARIA attributes
		 */
		setupAriaAttributes() {
			this.navItems.forEach(item => {
				const link = item.querySelector(this.config.selectors.navLink);
				const dropdown = item.querySelector(this.config.selectors.dropdown);
				const megaMenu = item.querySelector(this.config.selectors.megaMenu);
				const menu = dropdown || megaMenu;

				if (menu) {
					link?.setAttribute('aria-haspopup', 'true');
					link?.setAttribute('aria-expanded', 'false');
				}
			});
		},

		/**
		 * Destroy navigation instance
		 */
		destroy() {
			this.closeAllMenus();
			this.closeMobileMenu();
		},

		/**
		 * Close all menus
		 */
		closeAllMenus() {
			if (this.state.currentOpenMenu) {
				this.closeMenu(this.state.currentOpenMenu);
			}
		}
	};

	// Initialize navigation when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			PlaidNavigation.init();
			window.PlaidNavigation = PlaidNavigation;
		});
	} else {
		PlaidNavigation.init();
		window.PlaidNavigation = PlaidNavigation;
	}

	// Re-initialize after AJAX/dynamic content
	document.addEventListener('headerUpdated', () => {
		PlaidNavigation.init();
	});

})();
