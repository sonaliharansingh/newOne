@props(['align' => 'right', 'width' => '48'])

<div class="user-menu" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div x-show="open"
            x-cloak
            class="user-menu-panel"
            @click="open = false">
        {{ $content }}
    </div>
</div>
