<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - PSAU Tamarind RDE Center</title>

    
    <link href="<?php echo e(mix('css/app.css')); ?>" rel="stylesheet">
    <script src="<?php echo e(mix('js/app.js')); ?>" defer></script>

    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>
<body class="min-h-screen bg-gray-100 flex flex-col items-center justify-start pt-2">

    
    <img
        src="<?php echo e(asset('PSAU_Logo.png')); ?>"
        alt="Seal of Pampanga State Agricultural University"
        class="w-40 h-40 mb-4"
    />

    
    <div class="relative max-w-md w-full bg-white/30 backdrop-blur-md rounded-lg p-8 flex flex-col"
        style="background-color: rgba(255 255 255 / 0.3);">
        <div class="absolute top-0 left-0 h-full w-4 rounded-l-lg" style="background-color: #0b5e07;"></div>

        <h1 class="text-2xl font-extrabold text-green-800 mb-1 pl-2" style="font-family: Arial, sans-serif;">
            PSAU Tamarind RDE Center
        </h1>

        
        <form method="POST" action="<?php echo e(route('login')); ?>" class="flex flex-col space-y-4">
            <?php echo csrf_field(); ?>

            
            <?php if(session('error')): ?>
                <div class="text-red-700 text-sm mt-2 px-3 py-2 bg-red-100 border border-red-300 rounded">
                    <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?>

            
            <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-600">
                    <i class="fas fa-user"></i>
                </span>
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="<?php echo e(old('email')); ?>"
                    placeholder="Email"
                    class="pl-10 pr-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent w-full <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    required
                    autofocus
                />
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-gray-600">
                    <i class="fas fa-key"></i>
                </span>
                <input
                    id="password"
                    type="password"
                    name="password"
                    placeholder="Password"
                    class="pl-10 pr-10 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-700 focus:border-transparent w-full <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> border-red-500 <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                    required
                />
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="text-red-600 text-xs mt-1"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            
            <div class="flex items-center justify-between -mt-2">
                <label class="flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="remember" class="mr-2" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                    Remember Me
                </label>

                
                <?php if(Route::has('password.request')): ?>
                    <a href="<?php echo e(route('password.request')); ?>" class="text-sm text-black hover:underline">
                        Forgot Password?
                    </a>
                <?php endif; ?>
            </div>

            
            <button
                type="submit"
                class="w-full bg-green-800 text-white font-bold py-3 rounded-md hover:bg-green-900 transition-colors"
            >
                Login
            </button>
        </form>
    </div>

    
    <img
        src="<?php echo e(asset('tamarind-bg.png')); ?>"
        alt="Tamarind products background"
        class="fixed inset-0 -z-10 w-full h-full object-cover filter blur-sm brightness-75"
    />
</body>
</html>
<?php /**PATH /var/www/PSAU-CAP102/resources/views/auth/login.blade.php ENDPATH**/ ?>