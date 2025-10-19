<!DOCTYPE html>
<html lang="en">
<head>
    <?php $role = Auth::user()->role; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $__env->yieldContent('title', 'PSAU Tamarind RDE'); ?></title>
    <link rel="icon" href="/PSAU_Logo.png">
    <link href="<?php echo e(mix('css/app.css')); ?>" rel="stylesheet">
    <script src="<?php echo e(mix('js/app.js')); ?>" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" />
</head>

<?php echo $__env->yieldPushContent('scripts'); ?>
<body class="bg-gray-100 text-gray-900 min-h-screen flex">
    <!-- Sidebar (Desktop) -->
    <aside class="hidden md:flex fixed top-0 left-0 bg-[#003300] w-60 h-screen flex-col items-center py-6 text-white z-50">
        <?php echo $__env->make('components.navbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </aside>

    <!-- Mobile Top Bar -->
    <header class="md:hidden fixed top-0 left-0 right-0 bg-[#003300] text-white flex justify-between items-center px-4 py-3 z-40 shadow">
        <span class="font-bold text-lg">PSAU Tamarind R&DE</span>
        <button id="mobileMenuBtn" class="text-2xl p-2 rounded focus:outline-none focus:ring-2 focus:ring-white" aria-controls="mobileSidebar" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>
    </header>

    <!-- Mobile Sidebar (Slide-over) -->
    <div id="mobileSidebar"
        class="fixed inset-0 z-50 hidden md:hidden"
        aria-hidden="true">
        <!-- Backdrop -->
        <div id="backdrop"
            class="absolute inset-0 bg-black transition-opacity duration-300 opacity-0"></div>

        <!-- Panel -->
    <aside id="sidebarPanel"
        class="relative bg-[#003300] w-3/4 max-w-xs h-full p-6 text-white transform -translate-x-full transition-transform duration-300 ease-in-out overflow-y-auto rounded-r-2xl shadow-lg">
        
        <!-- Close button -->
        <button id="closeSidebar" 
                class="text-white text-2xl mb-6 p-2 rounded focus:outline-none focus:ring-2 focus:ring-white" 
                aria-label="Close menu">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Navbar links -->
        <nav class="flex flex-col items-center space-y-6 text-2xl md:text-lg font-bold">
            <?php echo $__env->make('components.navbar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </nav>
    </aside>


    </div>

    <!-- Page Wrapper -->
    <div class="flex flex-col flex-1 w-full md:ml-60">
        <!-- Spacer for fixed mobile header -->
        <div class="md:hidden h-[56px]"></div>
        <!-- Top Right User Info -->
        <div class="p-4 flex justify-end items-center gap-3">
            <?php if(auth()->guard()->check()): ?>
                <span class="text-sm md:text-base font-medium truncate max-w-[60%] md:max-w-none text-right">
                    <?php echo e(Auth::user()->name); ?> - <?php echo e($role); ?>

                </span>
                <div class="relative">
                    <button id="dropdownBtn" class="text-2xl p-2 rounded focus:outline-none focus:ring-2 focus:ring-gray-300" aria-haspopup="true" aria-expanded="false">
                        <i class="fa-solid fa-user"></i>
                    </button>
                    <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-40 bg-white shadow rounded-md overflow-hidden z-50">
                        <a href="<?php echo e(route('profile.index')); ?>" class="block px-4 py-2 text-sm hover:bg-gray-100">Profile</a>
                        <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" class="hidden"><?php echo csrf_field(); ?></form>
                        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="block px-4 py-2 text-sm hover:bg-gray-100">Logout</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if(auth()->guard()->guest()): ?>
                <span class="text-sm md:text-base font-medium truncate max-w-[60%] md:max-w-none text-right">
                    Guest
                </span>
            <?php endif; ?>
        </div>

        <!-- Page Content -->
        <main class="flex-grow px-3 py-4 md:p-6 z-0">
            <?php echo $__env->yieldContent('content'); ?>
        </main>
</div>

<!-- Toast Notifications -->
<style>
    @keyframes  slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes  slideUp {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-20px);
        }
    }

    @keyframes  pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.7;
        }
    }

    .-container {
        position: fixed;
        top: 24px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 50;
        animation: slideDown 0.4s ease-out;
    }

    .toast-container.removing {
        animation: slideUp 0.4s ease-out forwards;
    }

    .toast-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-left: 4px solid #047857;
    }

    .toast-error {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border-left: 4px solid #b91c1c;
    }

    .toast-warning {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        border-left: 4px solid #b45309;
    }

    .toast-icon {
        flex-shrink-0;
        animation: pulse 2s infinite;
    }
</style>

<?php if(session('success')): ?>
    <div class="toast-container toast-success text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-4 max-w-md">
<?php $__env->startSection('title', 'Edit Tree Data'); ?>
<?php $__env->startSection('content'); ?>
<main class="flex-1 p-6 space-y-6">
    <section class="bg-white rounded-lg shadow-md p-6 relative">
         <?php if (isset($component)) { $__componentOriginal5f1c24da064cdf37917762bf37a30d0804319ee8 = $component; } ?>
<?php $component = $__env->getContainer()->make(App\View\Components\Card::class, ['title' => 'Edit Tamarind Tree Data']); ?>
<?php $component->withName('card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php $component->withAttributes([]); ?>

            <!-- Back button -->
            <div class="flex justify-end mb-4">
                <a href="<?php echo e(url()->previous()); ?>"
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md shadow-sm hover:bg-gray-200 transition">
                    ← Back
                </a>
            </div>

            <!-- Form -->
            <form id="editTreeForm" action="<?php echo e(route('tree_data.update', $tree->tree_code_id)); ?>" method="POST" class="space-y-5">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <!-- Tree Code -->
                <div>
                    <label for="tree_code_id" class="block text-sm font-medium text-gray-700">ID</label>
                    <input type="text" name="tree_code_id" id="tree_code_id"
                           value="<?php echo e(old('tree_code_id', $tree->tree_code_id)); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- DBH -->
                <div>
                    <label for="dbh" class="block text-sm font-medium text-gray-700">DBH (cm)</label>
                    <input type="number" step="0.01" name="dbh" id="dbh"
                           value="<?php echo e(old('dbh', $tree->dbh)); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- Height -->
                <div>
                    <label for="height" class="block text-sm font-medium text-gray-700">Height (m)</label>
                    <input type="number" step="0.01" name="height" id="height"
                           value="<?php echo e(old('height', $tree->height)); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm"
                           required>
                </div>
                <!-- Age -->
                <div>
                    <label for="age" class="block text-sm font-medium text-gray-700">Age (years)</label>
                    <input type="number" name="age" id="age"
                           value="<?php echo e(old('age', $tree->age)); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Stem Diameter -->
                <div>
                    <label for="stem_diameter" class="block text-sm font-medium text-gray-700">Stem Diameter (cm)</label>
                    <input type="number" step="0.01" name="stem_diameter" id="stem_diameter"
                           value="<?php echo e(old('stem_diameter', $tree->stem_diameter)); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Canopy Diameter -->
                <div>
                    <label for="canopy_diameter" class="block text-sm font-medium text-gray-700">Canopy Diameter (m)</label>
                    <input type="number" step="0.01" name="canopy_diameter" id="canopy_diameter"
                           value="<?php echo e(old('canopy_diameter', $tree->canopy_diameter)); ?>"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm">
                </div>
                <!-- Submit -->
                <div class="pt-4">
                    <button type="submit"
                            class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition">
                        Update Tree Data
                    </button>
                </div>
            </form>
         <?php if (isset($__componentOriginal5f1c24da064cdf37917762bf37a30d0804319ee8)): ?>
<?php $component = $__componentOriginal5f1c24da064cdf37917762bf37a30d0804319ee8; ?>
<?php unset($__componentOriginal5f1c24da064cdf37917762bf37a30d0804319ee8); ?>
<?php endif; ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?> 
    </section>
</main>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-2">Confirm Update</h2>
            <p class="text-gray-600 mb-6">Are you sure you want to update this tree data? This action cannot be undone.</p>
            <div class="flex gap-3">
                <button id="confirmBtn" class="flex-1 rounded-lg bg-green-600 text-white py-2 hover:bg-green-700 transition font-medium">
                    Yes, Update
                </button>
                <button id="cancelBtn" class="flex-1 rounded-lg border border-gray-300 text-gray-700 py-2 hover:bg-gray-50 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>

        <svg class="toast-icon w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <span class="font-medium"><?php echo e(session('success')); ?></span>
    </div>

    <script>
        setTimeout(() => {
            const toast = document.querySelector('.toast-container.toast-success');
            if (toast) {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 400);
            }
        }, 4000);
    </script>
<?php endif; ?>

<?php if(session('error')): ?>
    <div class="toast-container toast-error text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-4 max-w-md">
        <svg class="toast-icon w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
        </svg>
        <span class="font-medium"><?php echo e(session('error')); ?></span>
    </div>

    <script>5
        setTimeout(() => {
            const toast = document.querySelector('.toast-container.toast-error');
            if (toast) {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 400);
            }
        }, 4000);
    </script>
<?php endif; ?>

<?php if(session('warning')): ?>
    <div class="toast-container toast-warning text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-4 max-w-md">
        <svg class="toast-icon w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
        <span class="font-medium"><?php echo e(session('warning')); ?></span>
    </div>

    <script>
        setTimeout(() => {
            const toast = document.querySelector('.toast-container.toast-warning');
            if (toast) {
                toast.classList.add('removing');
                setTimeout(() => toast.remove(), 400);
            }
        }, 4000);
    </script>
<?php endif; ?>

    <script>
        // User dropdown
        document.addEventListener('click', (e) => {
            const btn = document.getElementById('dropdownBtn');
            const menu = document.getElementById('dropdownMenu');
            if (!btn || !menu) return;

            if (btn.contains(e.target)) {
                menu.classList.toggle('hidden');
            } else if (!menu.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        // Mobile slide-over
        const mobileSidebar = document.getElementById('mobileSidebar');
        const sidebarPanel = document.getElementById('sidebarPanel');
        const backdrop = document.getElementById('backdrop');
        const openBtn = document.getElementById('mobileMenuBtn');
        const closeBtn = document.getElementById('closeSidebar');

        function openSidebar() {
            mobileSidebar.classList.remove('hidden');
            requestAnimationFrame(() => {
                sidebarPanel.classList.remove('-translate-x-full');
                backdrop.classList.remove('opacity-0');
                backdrop.classList.add('opacity-100');
                openBtn?.setAttribute('aria-expanded', 'true');
                mobileSidebar.setAttribute('aria-hidden', 'false');
            });
        }

        function closeSidebar() {
            sidebarPanel.classList.add('-translate-x-full');
            backdrop.classList.remove('opacity-100');
            backdrop.classList.add('opacity-0');
            setTimeout(() => {
                mobileSidebar.classList.add('hidden');
                openBtn?.setAttribute('aria-expanded', 'false');
                mobileSidebar.setAttribute('aria-hidden', 'true');
            }, 300);
        }

        openBtn?.addEventListener('click', openSidebar);
        closeBtn?.addEventListener('click', closeSidebar);
        backdrop?.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeSidebar(); });

        // Reset on resize
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                mobileSidebar.classList.add('hidden');
                sidebarPanel.classList.add('-translate-x-full');
                backdrop.classList.remove('opacity-100');
                backdrop.classList.add('opacity-0');
            }
        });
    </script>
</body>
</html>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/PSAU-CAP102/resources/views/layouts/app.blade.php ENDPATH**/ ?>