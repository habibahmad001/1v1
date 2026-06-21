/**
 * Plaid Navigation JavaScript
 *
 * Handles all navigation interactions including:
 * - Desktop mega menus with hover/click
 * - Mobile menu toggle and submenus
 * - Keyboard navigation
 * - Accessibility features
 * - Scroll effects
 *
 * @package PlaidNavChild
 */

(function() {
	'use strict';

	const Navigation = {
		config: {
			hoverDelay: 180,
			closeDelay: 320,
			scrollThreshold: 50,
			breakpoint: 1024,
			selectors: {
				header: '.plaid-header',
				navDesktop: '.plaid-nav-desktop',
				navItem: '.plaid-nav-item',
				navLink: '.plaid-nav-link',
				megaMenu: '.plaid-mega-menu',
				menuToggle: '[data-plaid-menu-toggle]',
				mobileToggle: '[data-plaid-mobile-toggle]',
				mobileOverlay: '[data-plaid-mobile-overlay]',
				mobileBackdrop: '[data-plaid-mobile-backdrop]',
				mobileToggleBtn: '[data-plaid-mobile-toggle-btn]',
				mobileSubmenu: '[data-plaid-mobile-submenu]',
			}
		},

		state: {
			currentOpenMenu: null,
			hoverTimer: null,
			closeTimer: null,
			isMobileMenuOpen: false,
			lastScrollPosition: 0,
			isHeaderScrolled: false,
		},

		init() {
			this.cacheElements();
			this.bindEvents();
			this.initScrollEffects();
			this.initKeyboardNavigation();
			this.initAccessibility();
		},

		cacheElements() {
			this.header = document.querySelector(this.config.selectors.header);
			this.navDesktop = document.querySelector(this.config.selectors.navDesktop);
			this.navItems = document.querySelectorAll(this.config.selectors.navItem);
			this.mobileToggle = document.querySelector(this.config.selectors.mobileToggle);
			this.mobileOverlay = document.querySelector(this.config.selectors.mobileOverlay);
			this.mobileBackdrop = document.querySelector(this.config.selectors.mobileBackdrop);
			this.mobileToggleBtns = document.querySelectorAll(this.config.selectors.mobileToggleBtn);
		},

		bindEvents() {
			this.bindDesktopEvents();
			this.bindMobileEvents();
			this.bindScrollEvents();
		},

		bindDesktopEvents() {
			if (!this.navDesktop) return;

			this.navItems.forEach(item => {
				const link = item.querySelector(this.config.selectors.navLink);
				const megaMenu = item.querySelector(this.config.selectors.megaMenu);

				if (!megaMenu) return;

				link.addEventListener('mouseenter', (e) => this.handleMenuEnter(e, item));
				link.addEventListener('mouseleave', (e) => this.handleMenuLeave(e, item));
				link.addEventListener('focus', (e) => this.handleMenuFocus(e, item));

				if (link.getAttribute('data-plaid-menu-toggle')) {
					link.addEventListener('click', (e) => this.handleMenuClick(e, item));
				}
			});

			document.addEventListener('click', (e) => {
				if (this.state.currentOpenMenu && !e.target.closest(this.config.selectors.navDesktop)) {
					this.closeAllMenus();
				}
			});
		},

		bindMobileEvents() {
			if (!this.mobileToggle) return;

			this.mobileToggle.addEventListener('click', () => this.toggleMobileMenu());

			if (this.mobileBackdrop) {
				this.mobileBackdrop.addEventListener('click', () => this.closeMobileMenu());
			}

			this.mobileToggleBtns.forEach(btn => {
				btn.addEventListener('click', (e) => this.toggleMobileSubmenu(e));
			});

			const mobileLinks = this.mobileOverlay?.querySelectorAll('a');
			mobileLinks?.forEach(link => {
				link.addEventListener('click', () => {
					if (window.innerWidth < this.config.breakpoint) {
						setTimeout(() => this.closeMobileMenu(), 100);
					}
				});
			});

			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape' && this.state.isMobileMenuOpen) {
					this.closeMobileMenu();
					this.mobileToggle?.focus();
				}
			});
		},

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

		handleScroll() {
			const scrollY = window.scrollY || window.pageYOffset;

			if (scrollY > this.config.selectors.scrollThreshold && !this.state.isHeaderScrolled) {
				this.header?.classList.add('scrolled');
				this.state.isHeaderScrolled = true;
			} else if (scrollY <= this.config.selectors.scrollThreshold && this.state.isHeaderScrolled) {
				this.header?.classList.remove('scrolled');
				this.state.isHeaderScrolled = false;
			}

			this.state.lastScrollPosition = scrollY;
		},

		handleMenuEnter(e, item) {
			if (window.innerWidth < this.config.breakpoint) return;

			clearTimeout(this.state.closeTimer);

			this.state.hoverTimer = setTimeout(() => {
				this.openMenu(item);
			}, this.config.hoverDelay);
		},

		handleMenuLeave(e, item) {
			if (window.innerWidth < this.config.breakpoint) return;

			clearTimeout(this.state.hoverTimer);

			const megaMenu = item.querySelector(this.config.selectors.megaMenu);
			if (!megaMenu) return;

			this.state.closeTimer = setTimeout(() => {
				const isStillOver = megaMenu.matches(':hover') || item.matches(':hover');
				if (!isStillOver) {
					this.closeMenu(item);
				}
			}, this.config.closeDelay);
		},

		handleMenuFocus(e, item) {
			if (window.innerWidth < this.config.breakpoint) return;

			if (this.state.currentOpenMenu && this.state.currentOpenMenu !== item) {
				this.closeMenu(this.state.currentOpenMenu);
			}

			this.openMenu(item);
		},

		handleMenuClick(e, item) {
			if (window.innerWidth < this.config.breakpoint) {
				e.preventDefault();
				this.toggleMenu(item);
			}
		},

		openMenu(item) {
			if (this.state.currentOpenMenu === item) return;

			if (this.state.currentOpenMenu) {
				this.closeMenu(this.state.currentOpenMenu);
			}

			const link = item.querySelector(this.config.selectors.navLink);
			const megaMenu = item.querySelector(this.config.selectors.megaMenu);

			if (link && megaMenu) {
				link.setAttribute('aria-expanded', 'true');
				megaMenu.classList.add('active');
				this.state.currentOpenMenu = item;
			}
		},

		closeMenu(item) {
			const link = item.querySelector(this.config.selectors.navLink);
			const megaMenu = item.querySelector(this.config.selectors.megaMenu);

			if (link && megaMenu) {
				link.setAttribute('aria-expanded', 'false');
				megaMenu.classList.remove('active');
			}

			if (this.state.currentOpenMenu === item) {
				this.state.currentOpenMenu = null;
			}
		},

		closeAllMenus() {
			this.navItems.forEach(item => this.closeMenu(item));
		},

		toggleMenu(item) {
			if (this.state.currentOpenMenu === item) {
				this.closeMenu(item);
			} else {
				this.openMenu(item);
			}
		},

		toggleMobileMenu() {
			if (this.state.isMobileMenuOpen) {
				this.closeMobileMenu();
			} else {
				this.openMobileMenu();
			}
		},

		openMobileMenu() {
			this.mobileToggle?.setAttribute('aria-expanded', 'true');
			this.mobileOverlay?.classList.add('active');
			this.mobileBackdrop?.classList.add('active');
			this.header?.classList.add('mobile-menu-active');
			document.body.style.overflow = 'hidden';
			this.state.isMobileMenuOpen = true;

			const firstLink = this.mobileOverlay?.querySelector('a');
			firstLink?.focus();
		},

		closeMobileMenu() {
			this.mobileToggle?.setAttribute('aria-expanded', 'false');
			this.mobileOverlay?.classList.remove('active');
			this.mobileBackdrop?.classList.remove('active');
			this.header?.classList.remove('mobile-menu-active');
			document.body.style.overflow = '';
			this.state.isMobileMenuOpen = false;

			this.closeAllMobileSubmenus();
		},

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

		openMobileSubmenu(btn, submenu) {
			btn.setAttribute('aria-expanded', 'true');
			submenu.classList.add('active');

			const parentItem = btn.closest('.plaid-mobile-item');
			parentItem?.classList.add('submenu-open');
		},

		closeMobileSubmenu(btn, submenu) {
			btn.setAttribute('aria-expanded', 'false');
			submenu.classList.remove('active');

			const parentItem = btn.closest('.plaid-mobile-item');
			parentItem?.classList.remove('submenu-open');
		},

		closeAllMobileSubmenus() {
			this.mobileToggleBtns.forEach(btn => {
				const targetId = btn.getAttribute('data-target');
				const submenu = document.getElementById(targetId);
				if (submenu && btn.getAttribute('aria-expanded') === 'true') {
					this.closeMobileSubmenu(btn, submenu);
				}
			});
		},

		initScrollEffects() {
			this.handleScroll();
		},

		initKeyboardNavigation() {
			document.addEventListener('keydown', (e) => {
				if (e.key === 'Escape') {
					this.closeAllMenus();
					if (this.state.isMobileMenuOpen) {
						this.closeMobileMenu();
					}
				}

				if (e.key === 'Tab') {
					this.handleTabNavigation(e);
				}
			});
		},

		handleTabNavigation(e) {
			if (!this.state.currentOpenMenu) return;

			const megaMenu = this.state.currentOpenMenu.querySelector(this.config.selectors.megaMenu);
			if (!megaMenu) return;

			const focusableElements = megaMenu.querySelectorAll(
				'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
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

		initAccessibility() {
			this.setupAriaAttributes();
			this.setupFocusManagement();
		},

		setupAriaAttributes() {
			this.navItems.forEach(item => {
				const link = item.querySelector(this.config.selectors.navLink);
				const megaMenu = item.querySelector(this.config.selectors.megaMenu);

				if (megaMenu) {
					link?.setAttribute('aria-haspopup', 'true');
					link?.setAttribute('aria-expanded', 'false');
				}
			});
		},

		setupFocusManagement() {
			const navContainer = document.querySelector(this.config.selectors.navDesktop);
			if (!navContainer) return;

			navContainer.addEventListener('focusin', (e) => {
				if (!e.target.closest(this.config.selectors.navDesktop)) {
					this.closeAllMenus();
				}
			});
		},

		destroy() {
			this.closeAllMenus();
			this.closeMobileMenu();

			this.navItems.forEach(item => {
				const link = item.querySelector(this.config.selectors.navLink);
				link?.removeEventListener('mouseenter', this.handleMenuEnter);
				link?.removeEventListener('mouseleave', this.handleMenuLeave);
				link?.removeEventListener('focus', this.handleMenuFocus);
			});

			this.mobileToggle?.removeEventListener('click', this.toggleMobileMenu);
			this.mobileBackdrop?.removeEventListener('click', this.closeMobileMenu);
		}
	};

	document.addEventListener('DOMContentLoaded', () => {
		Navigation.init();
		window.PlaidNavigation = Navigation;
	});

	document.addEventListener('headerUpdated', () => {
		Navigation.init();
	});

})();
