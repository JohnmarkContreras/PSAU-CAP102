

<?php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Log;

    $role = null;
    $unreadCount = 0;

    try {
        if (Auth::check()) {
            // Get role directly WITHOUT caching (cache loop is broken)
            $role = Auth::user()->getRoleNames()->first();
            
            // Get unread count directly WITHOUT caching
            $unreadCount = Auth::user()->unreadNotifications()->count();
        }
    } catch (\Throwable $e) {
        Log::error('Navbar data load failed: ' . $e->getMessage());
        $role = null;
        $unreadCount = 0;
    }
?>

<aside class="bg-[#003300] w-48 h-screen flex flex-col items-center py-6 text-white select-none">
    <img src="<?php echo e(asset('PSAU_Logo.png')); ?>" alt="Pamanga State Agricultural University official seal logo in green and yellow colors" class="mb-3" width="100" height="100" />
    <h1 class="font-extrabold text-sm mb-6">PSAU Tamarind R&DE</h1>

    <nav class="flex flex-col space-y-1 text-xs font-bold leading-tight">
        <?php if(auth()->guard()->check()): ?>
            
            <?php if($role === 'admin'): ?>
                <a href="<?php echo e(route('admin.dashboard')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('admin.dashboard') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fas fa-grip-horizontal text-lg text-gray-400"></i> Dashboard
                </a>
            <?php elseif($role === 'superadmin'): ?>
                <a href="<?php echo e(route('superadmin.dashboard')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('superadmin.dashboard') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fas fa-grip-horizontal text-lg text-gray-400"></i> Dashboard
                </a>
            <?php elseif($role === 'user'): ?>
                <a href="<?php echo e(route('user.dashboard')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('user.dashboard') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fas fa-grip-horizontal text-lg text-gray-400"></i> Dashboard
                </a>
            <?php endif; ?>

            
            <?php if($role === 'admin' || $role === 'superadmin'): ?>
                <a href="<?php echo e(route('tree-images.index')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('tree-images.index') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fas fa-map-pin text-xl text-gray-400"></i> Map
                </a>
                <a href="<?php echo e(route('analytics.carbon')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('analytics.carbon') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-chart-line text-lg text-gray-400"></i> Analytics
                </a>
                <a href="<?php echo e(route('accuracy.chart')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('accuracy.chart') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fas fa-map-pin text-xl text-gray-400"></i> Accuracy
                </a>
            <?php endif; ?>

            
            <?php if($role === 'user'): ?>
                <a href="<?php echo e(route('tree-images.index')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('tree-images.index') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fas fa-map-pin text-xl text-gray-400"></i> Map
                </a>
                <a href="<?php echo e(route('analytics.carbon')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('analytics.carbon') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-chart-line text-lg text-gray-400"></i> Analytics
                </a>
                <a href="<?php echo e(route('harvests.upcoming')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('harvests.upcoming') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-chart-pie text-lg text-gray-400"></i> Harvest Calendar
                </a>
            <?php endif; ?>

            
            <?php if($role === 'admin' || $role === 'superadmin'): ?>
                <a href="<?php echo e(route('pages.harvest-management')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('pages.harvest-management') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-chart-pie text-lg text-gray-400"></i> Harvest Management
                </a>
                <a href="<?php echo e(route('pages.backup')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('pages.backup') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-bars-progress text-lg text-gray-400"></i> Backup
                </a>
                <a href="<?php echo e(route('feedback.index')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('feedback.index') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-comment text-lg text-gray-400"></i> Manage Feedback
                </a>
                <a href="<?php echo e(route('pending-geotags.index')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('pending-geotags.index') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-hourglass-half text-lg text-gray-400"></i> Pending Tags
                </a>
            <?php endif; ?>

            
            <?php if($role === 'superadmin'): ?>
                <a href="<?php echo e(route('pages.accounts')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('pages.accounts') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-user text-lg text-gray-400"></i> Accounts
                </a>
                <a href="<?php echo e(route('pages.activity-log')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('pages.activity-log') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-list-check text-md text-gray-400"></i> Activity Log
                </a>
            <?php endif; ?>

            
            <?php if($role === 'admin'): ?>
                <a href="<?php echo e(route('admin.user_table')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('admin.user_table') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-user text-lg text-gray-400"></i> User
                </a>
            <?php endif; ?>

            
            <?php if($role === 'user'): ?>
                <a href="<?php echo e(route('feedback.create')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('feedback.create') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-comment text-lg text-gray-400"></i> Feedback
                </a>
                <a href="<?php echo e(route('pages.backup')); ?>" class="px-3 py-2 rounded flex items-center gap-3 <?php echo e(request()->routeIs('pages.backup') ? 'bg-[#1F7D53] text-white' : 'hover:underline'); ?>">
                    <i class="fa-solid fa-bars-progress text-lg text-gray-400"></i> Backup
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </nav>

    <div class="mt-auto text-[9px] px-2 text-white/80 select-text">© 2025 PSAU Tamarind RDE</div>
</aside><?php /**PATH /var/www/PSAU-CAP102/resources/views/components/navbar.blade.php ENDPATH**/ ?>