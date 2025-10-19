<div <?php echo e($attributes->merge(['class' => 'bg-white p-4 rounded shadow mb-4'])); ?>>
    <h2 class="text-green-800 font-extrabold text-2xl mb-2 border-l-4 border-green-800 pl-3"><?php echo e($title); ?></h2>
    <div><?php echo e($slot); ?></div> <!-- This is where you place inner content -->
</div>
<?php /**PATH /var/www/PSAU-CAP102/resources/views/components/card.blade.php ENDPATH**/ ?>