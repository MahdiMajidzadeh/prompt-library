<div class="page">
    <div class="read" style="padding: var(--spacing-9) 0;">
        <header style="margin-bottom: var(--spacing-7); text-align: center;">
            <h1 style="font-size: var(--text-2xl); font-weight: 600; letter-spacing: var(--tracking-tight); margin: 0 0 var(--spacing-2);">Sign in</h1>
            <p style="font-size: var(--text-sm); color: var(--color-text-secondary); margin: 0;">Admin access only.</p>
        </header>

        <form wire:submit="submit" class="pl-card" style="cursor: default; padding: var(--spacing-7);" novalidate>
            <div style="display: flex; flex-direction: column; gap: var(--spacing-2);">
                <label for="email" class="pl-sidebar__label" style="margin: 0;">Email</label>
                <input
                    id="email"
                    type="email"
                    autocomplete="email"
                    class="pl-search__input"
                    style="padding-left: var(--spacing-4);"
                    wire:model="email"
                    required
                >
                @error('email')
                    <p style="font-size: var(--text-xs); color: #B91C1C; margin: 0;">{{ $message }}</p>
                @enderror
            </div>

            <div style="display: flex; flex-direction: column; gap: var(--spacing-2);">
                <label for="password" class="pl-sidebar__label" style="margin: 0;">Password</label>
                <input
                    id="password"
                    type="password"
                    autocomplete="current-password"
                    class="pl-search__input"
                    style="padding-left: var(--spacing-4);"
                    wire:model="password"
                    required
                >
                @error('password')
                    <p style="font-size: var(--text-xs); color: #B91C1C; margin: 0;">{{ $message }}</p>
                @enderror
            </div>

            <label style="display: inline-flex; align-items: center; gap: var(--spacing-2); font-size: var(--text-sm); color: var(--color-text-secondary); cursor: pointer;">
                <input type="checkbox" wire:model="remember">
                <span>Remember me on this device</span>
            </label>

            <button type="submit" class="pl-btn pl-btn--primary" style="width: 100%;" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Sign in</span>
                <span wire:loading wire:target="submit">Signing in…</span>
            </button>
        </form>
    </div>
</div>
