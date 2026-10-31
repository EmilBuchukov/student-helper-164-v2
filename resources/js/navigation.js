// Main navigation entries; labels are translation keys.
export const mainNav = [
    { label: 'nav.home', route: 'home' },
    { label: 'nav.exams', route: 'exams.index', active: 'exams.*' },
    { label: 'nav.questions', route: 'questions.index', active: 'questions.*', hideFor: ['teacher'] },
    { label: 'nav.channels', route: 'channels.index', active: 'channels.*', hideFor: ['teacher'] },
    { label: 'nav.clubs', route: 'clubs.index', active: 'clubs.*' },
    { label: 'nav.events', route: 'events.index', active: 'events.*' },
];

export const adminNav = [
    { label: 'nav.admin_dashboard', route: 'admin.dashboard' },
    { label: 'nav.moderation_reports', route: 'moderation.reports.index', roles: ['admin', 'moderator'] },
    { label: 'nav.admin_users', route: 'admin.users.index', active: 'admin.users.*' },
    { label: 'nav.admin_whitelist', route: 'admin.whitelist.index' },
    { label: 'nav.admin_import', route: 'admin.students.import' },
    { label: 'nav.admin_deleted', route: 'admin.deleted.index' },
    { label: 'nav.admin_audit', route: 'admin.audit.index' },
];
