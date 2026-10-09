<?php $appBranding = app_branding($pdo); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($appBranding['product_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar-active { background-color: rgba(96, 165, 250, 0.18); border-left: 3px solid #60a5fa; color: #fff; }
        .custom-scrollbar { scrollbar-width: thin; scrollbar-color: #475569 transparent; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #475569; border: 1px solid transparent; border-radius: 10px; background-clip: padding-box; }
        .app-sidebar { position: fixed; inset: 0 auto 0 0; height: 100vh; height: 100dvh; min-height: 0; overflow: hidden; }
        .app-sidebar-nav { min-height: 0; overflow-y: auto; overscroll-behavior: contain; scrollbar-gutter: stable; }
        .page-search-hidden { display: none !important; }
        @media (min-width: 1024px) { .app-sidebar { position: sticky; inset: 0 auto auto 0; top: 0; align-self: flex-start; } }
        .dropdown-menu { display: none; }
        .dropdown-menu.show { display: block; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">
    <?php if (isset($_SESSION['staff_id'])): 
        $current_page = basename($_SERVER['PHP_SELF']);
    ?>
    <!-- Sidebar -->
    <div id="sidebarBackdrop" class="fixed inset-0 z-40 hidden bg-slate-950/55 backdrop-blur-sm lg:hidden"></div>
    <aside id="sidebar" class="app-sidebar z-50 bg-gradient-to-b from-slate-950 to-slate-900 text-white w-64 flex-shrink-0 transition-transform duration-300 ease-out hidden lg:flex flex-col border-r border-slate-800 shadow-2xl lg:z-auto lg:shadow-none">
        <div class="flex flex-shrink-0 items-center gap-3 border-b border-slate-800 px-5 py-4">
            <!-- Logo Placeholder -->
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white/10">
                <?php if ($appBranding['company_logo_path'] !== ''): ?>
                    <img src="brand_logo.php" alt="<?php echo e($appBranding['organization_name']); ?> logo" class="h-9 w-9 object-contain">
                <?php else: ?>
                    <span class="text-sm font-black tracking-tight text-sky-300"><?php echo e(strtoupper(substr($appBranding['product_name'], 0, 2))); ?></span>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <p class="truncate text-sm font-bold tracking-wide text-white"><?php echo e($appBranding['product_name']); ?></p>
                <p class="mt-0.5 text-[9px] font-semibold uppercase tracking-[0.18em] text-slate-400"><?php echo e($appBranding['organization_name']); ?></p>
            </div>
        </div>
        
        <div class="flex-shrink-0 border-b border-slate-800/80 px-3 py-3">
            <label for="sidebarSearch" class="sr-only">Filter navigation links</label>
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M19 10.5a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z"></path></svg>
                <input id="sidebarSearch" type="search" autocomplete="off" placeholder="Find a section..." class="w-full rounded-xl border border-white/10 bg-white/5 py-2 pl-9 pr-3 text-xs text-white placeholder:text-slate-500 outline-none transition focus:border-sky-400/50 focus:bg-white/10 focus:ring-2 focus:ring-sky-400/10">
            </div>
        </div>
        <nav id="mainNavigation" aria-label="Main navigation" class="app-sidebar-nav flex-1 px-3 py-3 space-y-1 custom-scrollbar">
            <?php if (auth_can_access_page('dashboard.php')): ?><p class="px-3 pb-1 pt-2 text-[9px] font-bold uppercase tracking-[0.18em] text-slate-500">Workspace</p><?php endif; ?>
            <?php if (auth_can_access_page('dashboard.php')): ?>
            <a href="dashboard.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'dashboard.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="font-medium text-sm">Dashboard</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('inventory.php') || auth_can_access_page('supplies.php')): ?><p class="px-3 pb-1 pt-5 text-[9px] font-bold uppercase tracking-[0.18em] text-slate-500">Inventory</p><?php endif; ?>
            <?php if (auth_can_access_page('inventory.php')): ?>
            <a href="inventory.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'inventory.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 11m8 4V5"></path></svg>
                <span class="font-medium text-sm">Inventory</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('keycards.php')): ?>
            <a href="keycards.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'keycards.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7zm0 3h18M7 15h3"></path></svg>
                <span class="font-medium text-sm">Keycards</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('supplies.php')): ?>
            <a href="supplies.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'supplies.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span class="font-medium text-sm">Supplies (Stock)</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('employees.php') || auth_can_access_page('issuance.php') || auth_can_access_page('room_management.php') || auth_can_access_page('maintenance.php') || auth_can_access_page('disposal.php')): ?><p class="px-3 pb-1 pt-5 text-[9px] font-bold uppercase tracking-[0.18em] text-slate-500">People &amp; operations</p><?php endif; ?>
            <?php if (auth_can_access_page('employees.php')): ?>
            <a href="employees.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'employees.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span class="font-medium text-sm">Employees</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('issuance.php')): ?>
            <a href="issuance.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'issuance.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                <span class="font-medium text-sm">Issuance</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('room_management.php')): ?>
            <a href="room_management.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'room_management.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10l9-7 9 7v10a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1V10z"></path></svg>
                <span class="font-medium text-sm">Item Deployment</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('maintenance.php')): ?>
            <a href="maintenance.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'maintenance.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span class="font-medium text-sm">Maintenance</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('disposal.php')): ?>
            <a href="disposal.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'disposal.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                <span class="font-medium text-sm">Asset Disposal</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('categories.php') || auth_can_access_page('vendors.php') || auth_can_access_page('reports.php') || auth_can_access_page('software_licenses.php')): ?><p class="px-3 pb-1 pt-5 text-[9px] font-bold uppercase tracking-[0.18em] text-slate-500">Directories &amp; reports</p><?php endif; ?>
            <?php if (auth_can_access_page('categories.php')): ?>
            <a href="categories.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'categories.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                <span class="font-medium text-sm">Categories</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('vendors.php')): ?>
            <a href="vendors.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'vendors.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <span class="font-medium text-sm">Vendors</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('reports.php')): ?>
            <a href="reports.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'reports.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span class="font-medium text-sm">Reports</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('software_licenses.php')): ?>
            <a href="software_licenses.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'software_licenses.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3v2.25M14.25 3v2.25M4.5 8.25h15M6.75 21h10.5A2.25 2.25 0 0019.5 18.75V8.25A2.25 2.25 0 0017.25 6h-10.5A2.25 2.25 0 004.5 8.25v10.5A2.25 2.25 0 006.75 21z"></path></svg>
                <span class="font-medium text-sm">Software Licenses</span>
            </a>
            <?php endif; ?>
            <?php if (auth_current_role() === ROLE_ADMIN): ?>
            <p class="px-3 pb-1 pt-5 text-[9px] font-bold uppercase tracking-[0.18em] text-slate-500">Administration</p>
            <a href="users.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'users.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span class="font-medium text-sm">User Management</span>
            </a>
            <a href="terms.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'terms.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                <span class="font-medium text-sm">Terms Editor</span>
            </a>
            <a href="pdf_template_editor.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'pdf_template_editor.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16M4 9h16M4 13h10M4 17h10M18 14v6m-3-3h6"></path></svg>
                <span class="font-medium text-sm">PDF Form Designer</span>
            </a>
            <a href="settings.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'settings.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317a1.724 1.724 0 013.35 0 1.724 1.724 0 002.573 1.002 1.724 1.724 0 012.367 2.367 1.724 1.724 0 001.002 2.573 1.724 1.724 0 010 3.35 1.724 1.724 0 00-1.002 2.573 1.724 1.724 0 01-2.367 2.367 1.724 1.724 0 00-2.573 1.002 1.724 1.724 0 01-3.35 0 1.724 1.724 0 00-2.573-1.002 1.724 1.724 0 01-2.367-2.367 1.724 1.724 0 00-1.002-2.573 1.724 1.724 0 010-3.35 1.724 1.724 0 001.002-2.573 1.724 1.724 0 012.367-2.367 1.724 1.724 0 002.573-1.002z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                <span class="font-medium text-sm">Settings</span>
            </a>
            <?php endif; ?>
            <!-- New Audit Trail Link -->
            <?php if (auth_can_access_page('activity_logs.php')): ?>
            <?php if (auth_can_access_page('activity_logs.php') || auth_can_access_page('file_manager.php')): ?><p class="px-3 pb-1 pt-5 text-[9px] font-bold uppercase tracking-[0.18em] text-slate-500">Activity &amp; files</p><?php endif; ?>
            <a href="activity_logs.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'activity_logs.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span class="font-medium text-sm">Activity Logs</span>
            </a>
            <?php endif; ?>
            <?php if (auth_can_access_page('file_manager.php')): ?>
            <a href="file_manager.php" class="flex items-center space-x-3 rounded-xl px-3 py-2.5 transition hover:bg-white/5 <?php echo $current_page == 'file_manager.php' ? 'sidebar-active' : 'text-slate-300'; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h5l2 2h7a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"></path></svg>
                <span class="font-medium text-sm">File Explorer</span>
            </a>
            <?php endif; ?>
        </nav>

        <div class="flex-shrink-0 border-t border-slate-800 bg-slate-950/50 p-3 text-center">
            <p class="text-[9px] text-slate-500 font-semibold uppercase tracking-widest">&copy; <?php echo date('Y'); ?> <?php echo e($appBranding['organization_name']); ?></p>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-grow flex flex-col min-w-0">
        <!-- Topbar -->
        <header class="bg-white shadow-sm border-b min-h-16 flex flex-wrap items-center justify-between gap-3 px-4 py-2 sticky top-0 z-40 lg:px-8">
            <div class="flex min-w-0 items-center space-x-3">
                <button id="toggleSidebar" aria-label="Open navigation menu" aria-expanded="false" class="lg:hidden rounded-xl p-2 text-gray-500 hover:bg-gray-100 hover:text-blue-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <h2 class="text-xl font-bold text-gray-800 capitalize tracking-tight">
                    <?php echo str_replace('.php', '', str_replace('_', ' ', $current_page)); ?>
                </h2>
            </div>

            <div class="order-3 w-full sm:order-none sm:w-auto sm:flex-1 sm:max-w-md lg:mx-8">
                <label for="pageSearch" class="sr-only">Search records on this page</label>
                <div class="relative">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M19 10.5a8.5 8.5 0 1 1-17 0 8.5 8.5 0 0 1 17 0Z"></path></svg>
                    <input id="pageSearch" type="search" autocomplete="off" placeholder="Search records on this page..." class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2 pl-10 pr-3 text-sm text-gray-700 outline-none transition placeholder:text-gray-400 focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                </div>
                <p id="pageSearchStatus" role="status" aria-live="polite" class="mt-1 hidden text-[10px] text-gray-500"></p>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative">
                <button id="profileDropdownBtn" class="flex items-center space-x-3 focus:outline-none group">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-gray-900 group-hover:text-blue-600 transition"><?php echo e(auth_current_name()); ?></p>
                        <p class="text-[10px] uppercase text-gray-500 font-black tracking-tighter"><?php echo e(auth_current_role()); ?></p>
                    </div>
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-800 font-black border-2 border-blue-200 group-hover:border-blue-400 transition">
                        <?php echo e(substr(auth_current_name(), 0, 1)); ?>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                
                <!-- Dropdown Content -->
                <div id="profileDropdown" class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-gray-100 py-2 z-50">
                    <a href="profile.php" class="flex items-center space-x-2 px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        <span>My Profile</span>
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <form action="logout.php" method="POST">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="flex w-full items-center space-x-2 px-4 py-2 text-left text-sm text-red-600 transition hover:bg-red-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="min-w-0 flex-grow px-4 py-5 lg:p-8">
    <?php endif; ?>
