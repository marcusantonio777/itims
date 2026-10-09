    <?php if (isset($_SESSION['staff_id'])): ?>
        </main>
        <footer class="bg-white border-t py-4 px-8 text-center text-gray-500 text-[10px] font-bold uppercase tracking-widest">
            &copy; <?php echo date('Y'); ?> Quest Hotel IT Inventory Management System. All rights reserved.
        </footer>
    </div> <!-- Close Main Content Area -->

    <script>
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const sidebarSearch = document.getElementById('sidebarSearch');
        const mainNavigation = document.getElementById('mainNavigation');
        const pageSearch = document.getElementById('pageSearch');
        const pageSearchStatus = document.getElementById('pageSearchStatus');
        const profileDropdownBtn = document.getElementById('profileDropdownBtn');
        const profileDropdown = document.getElementById('profileDropdown');

        function setMobileSidebarOpen(open) {
            if (!sidebar || !toggleBtn || !sidebarBackdrop) return;
            sidebar.classList.toggle('hidden', !open);
            sidebar.classList.toggle('flex', open);
            sidebarBackdrop.classList.toggle('hidden', !open);
            toggleBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            document.body.classList.toggle('overflow-hidden', open);
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => setMobileSidebarOpen(sidebar.classList.contains('hidden')));
        }
        if (sidebarBackdrop) sidebarBackdrop.addEventListener('click', () => setMobileSidebarOpen(false));
        if (sidebar) {
            sidebar.querySelectorAll('nav a').forEach((link) => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 1024) setMobileSidebarOpen(false);
                });
            });
        }
        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar && !sidebar.classList.contains('hidden') && window.innerWidth < 1024) {
                setMobileSidebarOpen(false);
            }
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                if (sidebar) sidebar.classList.remove('hidden');
                if (sidebarBackdrop) sidebarBackdrop.classList.add('hidden');
                if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
                document.body.classList.remove('overflow-hidden');
            } else if (sidebar) {
                sidebar.classList.add('hidden');
            }
        });

        if (sidebarSearch && mainNavigation) {
            sidebarSearch.addEventListener('input', () => {
                const query = sidebarSearch.value.trim().toLocaleLowerCase();
                const children = Array.from(mainNavigation.children);
                let sectionHeading = null;
                let sectionHasMatch = false;
                const updateHeading = () => {
                    if (sectionHeading) sectionHeading.hidden = query !== '' && !sectionHasMatch;
                };

                children.forEach((item) => {
                    if (item.tagName === 'P') {
                        updateHeading();
                        sectionHeading = item;
                        sectionHasMatch = false;
                    } else if (item.tagName === 'A') {
                        const matches = query === '' || item.textContent.toLocaleLowerCase().includes(query);
                        item.hidden = !matches;
                        if (matches) sectionHasMatch = true;
                    }
                });
                updateHeading();
            });
        }

        if (pageSearch && pageSearchStatus) {
            pageSearch.addEventListener('input', () => {
                const query = pageSearch.value.trim().toLocaleLowerCase();
                const tables = Array.from(document.querySelectorAll('main table')).filter((table) => !table.closest('[contenteditable="true"]'));
                let searchableRows = 0;
                let matchedRows = 0;

                tables.forEach((table) => {
                    table.querySelectorAll('tbody tr').forEach((row) => row.classList.remove('page-search-hidden'));
                });

                if (query) {
                    tables.forEach((table) => {
                        table.querySelectorAll('tbody tr').forEach((row) => {
                            if (getComputedStyle(row).display === 'none') return;
                            searchableRows++;
                            const matches = row.textContent.toLocaleLowerCase().includes(query);
                            row.classList.toggle('page-search-hidden', !matches);
                            if (matches) matchedRows++;
                        });
                    });
                    pageSearchStatus.textContent = searchableRows === 0
                        ? 'No visible records to search.'
                        : `${matchedRows} of ${searchableRows} visible records match.`;
                    pageSearchStatus.classList.remove('hidden');
                } else {
                    pageSearchStatus.textContent = '';
                    pageSearchStatus.classList.add('hidden');
                }
            });
        }

        // Profile Dropdown Toggle
        if (profileDropdownBtn) {
            profileDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('show');
            });
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', () => {
            if (profileDropdown && profileDropdown.classList.contains('show')) {
                profileDropdown.classList.remove('show');
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
