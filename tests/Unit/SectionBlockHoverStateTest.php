<?php

it('keeps the homepage section card hover state free from typography shifts', function () {
    $component = file_get_contents(__DIR__.'/../../resources/js/pages/SectionBlock.vue');

    expect($component)
        ->toContain('shadow-sm transition duration-300 ease-in-out')
        ->toContain('transition-transform duration-300 ease-in-out')
        ->toContain('motion-safe:hover:-translate-y-1')
        ->toContain('motion-safe:group-hover:translate-x-1')
        ->not->toContain('group-hover:text-2xl')
        ->not->toContain('group-hover:font-bold');
});
