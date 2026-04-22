import { useEffect, useState } from 'react';

const STORAGE_KEY = 'theme';
const DEFAULT_THEME = 'dark';

function resolveInitialTheme() {
    if (typeof document === 'undefined') {
        return DEFAULT_THEME;
    }

    const explicit = document.documentElement.dataset.theme;

    return explicit === 'light' || explicit === 'dark' ? explicit : DEFAULT_THEME;
}

export function useTheme() {
    const [theme, setThemeState] = useState(resolveInitialTheme);

    useEffect(() => {
        const root = document.documentElement;

        root.classList.toggle('dark', theme === 'dark');
        root.dataset.theme = theme;
        localStorage.setItem(STORAGE_KEY, theme);
    }, [theme]);

    return {
        theme,
        setTheme: setThemeState,
        toggleTheme: () => setThemeState((current) => (current === 'dark' ? 'light' : 'dark')),
    };
}
