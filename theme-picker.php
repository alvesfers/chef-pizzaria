<?php
$temas = [
    'light',
    'dark',
    'cupcake',
    'bumblebee',
    'emerald',
    'corporate',
    'synthwave',
    'retro',
    'cyberpunk',
    'valentine',
    'halloween',
    'garden',
    'forest',
    'aqua',
    'lofi',
    'pastel',
    'fantasy',
    'wireframe',
    'black',
    'luxury',
    'dracula',
    'cmyk',
    'autumn',
    'business',
    'acid',
    'lemonade',
    'night',
    'coffee',
    'winter',
    'dim',
    'nord',
    'sunset',
    'caramellatte',
    'abyss',
    'silk'
];
?>

<?php foreach ($temas as $tema): ?>
    <div class="border-base-content/20 hover:border-base-content/40 overflow-hidden rounded-lg border outline-2 outline-offset-2 outline-transparent cursor-pointer"
        data-set-theme="<?= $tema ?>" data-act-class="outline-base-content!">
        <div data-theme="<?= $tema ?>" class="bg-base-100 text-base-content w-full font-sans">
            <div class="grid grid-cols-5 grid-rows-3">
                <div class="bg-base-200 col-start-1 row-span-2 row-start-1"></div>
                <div class="bg-base-300 col-start-1 row-start-3"></div>
                <div class="bg-base-100 col-span-4 col-start-2 row-span-3 row-start-1 flex flex-col gap-1 p-2">
                    <div class="font-bold text-sm"><?= $tema ?></div>
                    <div class="flex flex-wrap gap-1">
                        <div class="bg-primary w-5 h-5 lg:w-6 lg:h-6 rounded flex items-center justify-center">
                            <div class="text-primary-content text-xs font-bold">A</div>
                        </div>
                        <div class="bg-secondary w-5 h-5 lg:w-6 lg:h-6 rounded flex items-center justify-center">
                            <div class="text-secondary-content text-xs font-bold">A</div>
                        </div>
                        <div class="bg-accent w-5 h-5 lg:w-6 lg:h-6 rounded flex items-center justify-center">
                            <div class="text-accent-content text-xs font-bold">A</div>
                        </div>
                        <div class="bg-neutral w-5 h-5 lg:w-6 lg:h-6 rounded flex items-center justify-center">
                            <div class="text-neutral-content text-xs font-bold">A</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>