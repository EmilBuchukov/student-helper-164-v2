// Minimal i18n: translations come from lang/{locale}/ui.php via the shared Inertia prop `translations`.
let messages = {};

export function setTranslations(translations) {
    messages = translations || {};
}

export function t(key, replacements = {}) {
    let message = messages[key] ?? key;

    for (const [name, value] of Object.entries(replacements)) {
        message = message.replaceAll(`:${name}`, String(value));
    }

    return message;
}

export const i18n = {
    install(app, { translations }) {
        setTranslations(translations);
        app.config.globalProperties.$t = t;
    },
};
