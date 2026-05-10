const Router = {
    routes: {},
    currentPage: null,

    register(path, handler) {
        this.routes[path] = handler;
    },

    init() {
        window.addEventListener('hashchange', () => this.resolve());
        window.addEventListener('load', () => this.resolve());
    },

    navigate(path) {
        window.location.hash = path;
    },

    resolve() {
        const hash = window.location.hash.slice(1) || '/';
        const [path, ...paramParts] = hash.split('/').filter(Boolean);
        const params = paramParts.join('/');

        const page = path || 'home';
        const handler = this.routes[page];

        if (handler) {
            this.currentPage = page;
            handler(params);
            this.updateNav(page);
        } else if (this.routes['home']) {
            this.currentPage = 'home';
            this.routes['home']();
            this.updateNav('home');
        }
    },

    updateNav(page) {
        document.querySelectorAll('.nav-link, .mobile-menu-link').forEach(link => {
            link.classList.toggle('active', link.dataset.page === page);
        });
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenu) mobileMenu.classList.remove('open');
    }
};
