import { cva } from 'class-variance-authority';

export const buttonVariants = cva(
    'relative inline-flex select-none items-center justify-center gap-2 whitespace-nowrap rounded font-medium transition duration-150 ease-out focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus active:translate-y-px disabled:pointer-events-none disabled:opacity-55 [&_svg]:size-[1.1em] [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                primary: 'bg-primary text-on-primary shadow-[inset_0_-2px_0_rgb(0_0_0/0.18)] hover:bg-primary-hover',
                secondary: 'border border-line-strong bg-surface text-ink hover:border-ink-subtle hover:bg-sunken',
                ghost: 'text-ink-muted hover:bg-sunken hover:text-ink',
                accent: 'bg-accent text-on-accent shadow-[inset_0_-2px_0_rgb(0_0_0/0.12)] hover:brightness-95',
                danger: 'border border-danger/40 bg-surface text-danger hover:bg-danger-soft',
                link: 'h-auto px-0 text-primary-ink underline decoration-accent decoration-2 underline-offset-4 hover:decoration-primary-ink',
            },
            size: {
                sm: 'h-9 px-3 text-sm',
                md: 'h-11 px-4 text-[0.9375rem]',
                lg: 'h-12 px-6 text-base',
            },
            block: { true: 'w-full' },
        },
        defaultVariants: { variant: 'primary', size: 'md' },
    },
);

export const badgeVariants = cva(
    'inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium leading-5 [&_svg]:size-3.5',
    {
        variants: {
            tone: {
                neutral: 'bg-sunken text-ink-muted',
                primary: 'bg-primary-soft text-primary-ink',
                accent: 'bg-accent-soft text-accent-ink',
                success: 'bg-success-soft text-success',
                warning: 'bg-warning-soft text-warning',
                danger: 'bg-danger-soft text-danger',
                info: 'bg-info-soft text-info',
                outline: 'border border-line-strong text-ink-muted',
            },
        },
        defaultVariants: { tone: 'neutral' },
    },
);

export const fieldClasses =
    'block w-full rounded border border-line-strong bg-surface px-3 text-[0.9375rem] text-ink shadow-none transition duration-150 placeholder:text-ink-subtle hover:border-ink-subtle focus:border-primary-ink focus:outline-none focus:ring-2 focus:ring-focus/30 disabled:cursor-not-allowed disabled:bg-sunken disabled:opacity-70 aria-[invalid=true]:border-danger';
