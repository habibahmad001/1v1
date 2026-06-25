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
				mobileSubmenu: '.plaid-mobile-submenu',
				mobileMenu: '.plaid-mobile-menu',
				mobilePanels: '.plaid-mobile-panels',
				mobilePanel: '.plaid-mobile-panel',
				mobilePanelRoot: '.plaid-mobile-panel--root',
				mobilePanelArrow: '.plaid-mobile-panel-arrow',
				mobileBackBtn: '.plaid-mobile-back-btn',
				mobileCloseBtn: '.plaid-mobile-close-btn',
				mobileLogo: '.plaid-mobile-menu-logo',
				mobileMenuHeader: '.plaid-mobile-menu-header'
			}
		},

		// State management
		state: {
			currentOpenMenu: null,
			hoverTimer: null,
			closeTimer: null,
			isMobileOpen: false,
			scrollY: 0,
			isScrolled: false,
			panelStack: [],
			currentPanel: null
		},

		// Track if events have been bound to prevent duplicates
		eventsBound: false,
		abortController: null,

		/**
		 * Initialize the navigation system
		 */
		init() {
			// Clean up any existing event listeners
			if (this.abortController) {
				this.abortController.abort();
			}
			this.abortController = new AbortController();
			const signal = this.abortController.signal;

			this.cacheElements();
			this.bindEvents(signal);
			this.eventsBound = true;

			this.initScrollEffects();
			this.initKeyboardNavigation(signal);
			this.initAccessibility();
			this.handleResize();
		},

		/**
		 * Cache DOM elements
		 */
		/**
		 * Cache DOM elements
		 */
		cacheElements() {
			console.log('[PlaidNav] Initializing navigation...');
			this.header = document.querySelector(this.config.selectors.header);
			this.desktopNav = document.querySelector(this.config.selectors.desktopNav);
			this.mobileNav = document.querySelector(this.config.selectors.mobileNav);
			this.mobileToggle = document.querySelector(this.config.selectors.mobileToggle);
			console.log('[PlaidNav] mobileToggle found:', this.mobileToggle);

			this.mobileBackdrop = document.getElementById('plaid-mobile-backdrop');
			this.mobileMenu = document.getElementById('plaid-mobile-menu');
			console.log('[PlaidNav] mobileMenu found:', this.mobileMenu);

			this.navItems = document.querySelectorAll(this.config.selectors.navItem);

			// Panel navigation elements
			this.mobilePanels = document.querySelector(this.config.selectors.mobilePanels);
			this.mobileCloseBtn = document.getElementById('plaid-mobile-close-btn');
			this.mobileBackBtn = document.getElementById('plaid-mobile-back-btn');
			this.mobileLogo = document.getElementById('plaid-mobile-logo');
			this.rootPanel = document.querySelector(this.config.selectors.mobilePanelRoot);
			console.log('[PlaidNav] rootPanel found:', this.rootPanel);

			if (!this.mobileToggle) {
				console.error('[PlaidNav] Mobile toggle button not found! Selector: ' + this.config.selectors.mobileToggle);
			}
			if (!this.mobileMenu) {
				console.error('[PlaidNav] Mobile menu not found!');
			}
		},

		/**
		 * Bind all event listeners
		 */
		bindEvents(signal) {
			this.bindDesktopEvents(signal);
			this.bindMobileEvents(signal);
			this.bindScrollEvents(signal);
			this.bindResizeEvents(signal);
		},

		/**
		 * Desktop navigation events
		 */
		bindDesktopEvents(signal) {
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
				}, { signal });

				// Keyboard navigation - focus opens menu (but not after click)
				link.addEventListener('focus', (e) => {
					// Only handle focus from keyboard tab, not from click
					if (e.relatedTarget !== null) {
						this.handleMenuFocus(item);
					}
				}, { signal });
			});

			// Click outside to close
			document.addEventListener('click', (e) => {
				if (this.state.currentOpenMenu && !e.target.closest(this.config.selectors.desktopNav)) {
					this.closeMenu(this.state.currentOpenMenu);
				}
			}, { signal });
		},

		/**
		 * Mobile navigation events
		 */
			/**
			 * Mobile navigation events
			 */
			bindMobileEvents(signal) {
				console.log('[PlaidNav] bindMobileEvents called, mobileToggle:', this.mobileToggle);
				if (!this.mobileToggle) {
					console.error('[PlaidNav] mobileToggle is null, cannot bind events');
					return;
				}

				// Toggle mobile menu
				this.mobileToggle.addEventListener('click', (e) => {
					console.log('[PlaidNav] Toggle button clicked!');
					this.toggleMobileMenu();
				}, { signal });

				// Backdrop click to close
				if (this.mobileBackdrop) {
					this.mobileBackdrop.addEventListener('click', () => {
						this.closeMobileMenu();
					}, { signal });
				}

				// Close button in mobile menu header
				if (this.mobileCloseBtn) {
					this.mobileCloseBtn.addEventListener('click', () => {
						this.closeMobileMenu();
					}, { signal });
				}

				// Back button for panel navigation
				if (this.mobileBackBtn) {
					this.mobileBackBtn.addEventListener('click', () => {
						this.navigateBack();
					}, { signal });
				}

				// Panel arrow clicks - use event delegation
				this.bindPanelNavigation(signal);

				// Escape key to close mobile menu
				document.addEventListener('keydown', (e) => {
					if (e.key === 'Escape' && this.state.isMobileOpen) {
						if (this.state.panelStack.length > 0) {
							// If in a submenu, go back
							this.navigateBack();
						} else {
							// Otherwise close the menu
							this.closeMobileMenu();
							this.mobileToggle?.focus();
						}
					}
				}, { signal });
			},
		bindPanelNavigation(signal) {
			const mobileMenu = document.getElementById('plaid-mobile-menu');
			console.log('[PanelNav] bindPanelNavigation called, mobileMenu:', mobileMenu);
			if (!mobileMenu) return;

			// Use event delegation for panel arrow clicks
			mobileMenu.addEventListener('click', (e) => {
				const arrowBtn = e.target.closest(this.config.selectors.mobilePanelArrow);
				console.log('[PanelNav] Click detected, arrowBtn:', arrowBtn, 'e.target:', e.target);
				if (!arrowBtn) return;

				e.preventDefault();
				e.stopPropagation();

				const targetId = arrowBtn.getAttribute('data-panel-target');
				console.log('[PanelNav] targetId:', targetId);
				if (targetId) {
					this.navigateToPanel(targetId);
				}
			}, { signal });
		},


				/**
				 * Navigate to a specific panel - simple show/hide, no animations
				 */
				navigateToPanel(targetId) {
					console.log('[PanelNav] navigateToPanel called with targetId:', targetId);
					const targetPanel = document.getElementById('plaid-mobile-panel-' + targetId);
					console.log('[PanelNav] Looking for panel ID: plaid-mobile-panel-' + targetId);
					console.log('[PanelNav] targetPanel found:', targetPanel);
					if (!targetPanel) {
						console.error('[PanelNav] Panel not found! Available panels:', document.querySelectorAll('.plaid-mobile-panel'));
						return;
					}

					const currentPanel = this.state.currentPanel;
					console.log('[PanelNav] currentPanel:', currentPanel);
					if (!currentPanel) {
						console.error('[PanelNav] currentPanel is null!');
						return;
					}

					// Push current panel to stack for back navigation
					this.state.panelStack.push(currentPanel);

					// Hide current panel, show target panel
					console.log('[PanelNav] Hiding current panel, showing target panel');
					currentPanel.classList.remove('active');
					targetPanel.classList.add('active');

					// Update current panel
					this.state.currentPanel = targetPanel;

					// Update header to show back button
					this.updateMobileHeader(true);

					// Focus first item in new panel
					const firstLink = targetPanel.querySelector('a, button');
					firstLink?.focus();
					console.log('[PanelNav] Navigation complete');
				},
			navigateBack() {
				if (this.state.panelStack.length === 0) return;

				const currentPanel = this.state.currentPanel;
				const previousPanel = this.state.panelStack.pop();

				if (!previousPanel || !currentPanel) return;

				// Hide current panel, show previous panel
				currentPanel.classList.remove('active');
				previousPanel.classList.add('active');

				// Update current panel
				this.state.currentPanel = previousPanel;

				// Update header (show logo if at root, otherwise show back)
				const showBack = this.state.panelStack.length > 0;
				this.updateMobileHeader(showBack);

				// Focus first item in previous panel
				const firstLink = previousPanel.querySelector('a, button');
				firstLink?.focus();
			},

			updateMobileHeader(showBack) {
				if (!this.mobileLogo || !this.mobileBackBtn) return;

				if (showBack) {
					// Show back button, hide logo
					this.mobileBackBtn.style.display = 'flex';
					this.mobileLogo.style.display = 'none';
				} else {
					// Show logo, hide back button
					this.mobileLogo.style.display = 'flex';
					this.mobileBackBtn.style.display = 'none';
				}
			},
		bindScrollEvents(signal) {
			let ticking = false;

			window.addEventListener('scroll', () => {
				if (!ticking) {
					window.requestAnimationFrame(() => {
						this.handleScroll();
						ticking = false;
					});
					ticking = true;
				}
			}, { passive: true }, { signal });
		},

		/**
		 * Resize events
		 */
		bindResizeEvents(signal) {
			let resizeTimer;
			window.addEventListener('resize', () => {
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(() => {
					this.handleResize();
				}, 250);
			}, { signal });
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
				this.updateDropdownPosition();

				link.setAttribute('aria-expanded', 'true');
				menu.classList.add('active');
				this.state.currentOpenMenu = item;
			}
		},

		/**
		 * Update dropdown position dynamically
		 */
		updateDropdownPosition() {
			const headerContainer = document.querySelector(this.config.selectors.container);
			if (headerContainer) {
				const rect = headerContainer.getBoundingClientRect();
				const topPosition = rect.bottom + 30; // Header bottom + 30px gap
				document.documentElement.style.setProperty('--plaid-dropdown-top', topPosition + 'px');
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
			console.log('[PlaidNav] toggleMobileMenu called, isMobileOpen:', this.state.isMobileOpen);
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
			this.mobileMenu?.classList.add('active');
			this.mobileMenu?.style.setProperty('display', 'flex', 'important');
			this.mobileBackdrop?.classList.add('active');
			this.mobileBackdrop?.style.setProperty('display', 'block', 'important');
			this.header?.classList.add('mobile-menu-active');
			document.body.style.overflow = 'hidden';
			this.state.isMobileOpen = true;

			// Reset panel navigation
			this.resetPanelNavigation();

			// Focus first menu item
			const firstLink = this.rootPanel?.querySelector('a, button');
			firstLink?.focus();
		},
		closeMobileMenu() {
			this.mobileToggle?.setAttribute('aria-expanded', 'false');
			this.mobileMenu?.classList.remove('active');
			this.mobileMenu?.style.setProperty('display', 'none', 'important');
			this.mobileBackdrop?.classList.remove('active');
			this.mobileBackdrop?.style.setProperty('display', 'none', 'important');
			this.header?.classList.remove('mobile-menu-active');
			document.body.style.overflow = '';
			this.state.isMobileOpen = false;

			this.resetPanelNavigation();
		},

		/**
		 * Toggle mobile submenu
		 */

		/**
		 * Open mobile submenu
		 */

		/**
		 * Close mobile submenu
		 */

		/**
		 * Close all mobile submenus
		 */

		/**
		 * Handle scroll

		/**
		 * Reset panel navigation to initial state
		 */
		resetPanelNavigation() {
			// Clear panel stack
			this.state.panelStack = [];
			this.state.currentPanel = this.rootPanel;
				// Re-cache root panel in case it wasn't available during init
				if (!this.rootPanel) {
					this.rootPanel = document.querySelector(this.config.selectors.mobilePanelRoot);
				}

				this.state.currentPanel = this.rootPanel;

			// Reset all panel classes
			const allPanels = document.querySelectorAll(this.config.selectors.mobilePanel);
			allPanels.forEach(panel => {
			panel.classList.remove('active');
			});

			// Ensure root panel is active
			if (this.rootPanel) {
				this.rootPanel.classList.add('active');
			}

			// Reset header to show logo
			this.updateMobileHeader(false);
		},
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

			// Update dropdown position on resize
			this.updateDropdownPosition();
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
		initKeyboardNavigation(signal) {
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
			}, { signal });
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

	// Track initialization state to prevent duplicate event listeners
	let isInitialized = false;

	// Initialize navigation when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', () => {
			PlaidNavigation.init();
			window.PlaidNavigation = PlaidNavigation;
			isInitialized = true;
		});
	} else {
		PlaidNavigation.init();
		window.PlaidNavigation = PlaidNavigation;
		isInitialized = true;
	}

	// Re-initialize after AJAX/dynamic content (prevent duplicate event binding)
	document.addEventListener('headerUpdated', () => {
		if (!isInitialized) {
			PlaidNavigation.init();
			isInitialized = true;
		} else {
			// Only update cached elements, don't re-bind events
			PlaidNavigation.cacheElements();
		}
	});

})();
