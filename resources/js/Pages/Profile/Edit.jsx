import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { MailCheck, ShieldAlert, Trash2, UserRound } from 'lucide-react';

import { AppSidebar } from '@/components/app-sidebar';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

function FieldError({ message }) {
    if (!message) {
        return null;
    }

    return <p className="mt-2 text-sm text-destructive">{message}</p>;
}

export default function ProfileEdit({ auth, profile, urls, flash, errors }) {
    const page = usePage();
    const validationErrors = errors ?? page.props.errors ?? {};
    const updatePasswordErrors = validationErrors.updatePassword ?? {};
    const userDeletionErrors = validationErrors.userDeletion ?? {};

    const profileForm = useForm({
        name: profile.name,
        email: profile.email,
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const deletionForm = useForm({
        password: '',
    });

    function saveProfile(event) {
        event.preventDefault();
        profileForm.patch(urls.updateProfile, {
            preserveScroll: true,
        });
    }

    function savePassword(event) {
        event.preventDefault();
        passwordForm.put(urls.updatePassword, {
            preserveScroll: true,
            errorBag: 'updatePassword',
            onSuccess: () => passwordForm.reset(),
        });
    }

    function deleteAccount(event) {
        event.preventDefault();
        deletionForm.delete(urls.deleteProfile, {
            preserveScroll: true,
            errorBag: 'userDeletion',
        });
    }

    return (
        <>
            <Head title="Profile" />

            <SidebarProvider defaultOpen>
                <AppSidebar auth={auth} urls={urls} activePage="profile" />

                <SidebarInset className="bg-background">
                    <header className="sticky top-0 z-20 border-b border-border/70 bg-background/85 backdrop-blur">
                        <div className="flex flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-start gap-3">
                                <SidebarTrigger className="mt-1" />
                                <Separator orientation="vertical" className="mt-1 hidden h-6 bg-border lg:block" />

                                <div>
                                    <div className="inline-flex items-center gap-2 rounded-full border border-primary/25 bg-primary/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.22em] text-primary">
                                        <UserRound className="size-3.5" />
                                        Profile
                                    </div>
                                    <h1 className="mt-3 text-2xl font-semibold tracking-tight text-foreground">
                                        Manage account details and security
                                    </h1>
                                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                                        Update your identity details, rotate your password, and control account access.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                                <ThemeToggle />
                                <Button asChild variant="outline">
                                    <Link href={urls.dashboard}>Dashboard</Link>
                                </Button>
                            </div>
                        </div>
                    </header>

                    <div className="flex-1 px-4 py-6 sm:px-6">
                        <div className="grid gap-6 xl:grid-cols-[1fr_1fr]">
                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <h2 className="text-xl font-semibold text-card-foreground">Profile information</h2>
                                <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                    Update your account&apos;s profile information and email address.
                                </p>

                                <form onSubmit={saveProfile} className="mt-6 space-y-5">
                                    <div>
                                        <label className="block text-sm font-medium text-foreground" htmlFor="name">
                                            Name
                                        </label>
                                        <Input
                                            id="name"
                                            value={profileForm.data.name}
                                            onChange={(event) => profileForm.setData('name', event.target.value)}
                                            className="mt-2"
                                            required
                                            autoFocus
                                            autoComplete="name"
                                        />
                                        <FieldError message={validationErrors.name} />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-foreground" htmlFor="email">
                                            Email
                                        </label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={profileForm.data.email}
                                            onChange={(event) => profileForm.setData('email', event.target.value)}
                                            className="mt-2"
                                            required
                                            autoComplete="username"
                                        />
                                        <FieldError message={validationErrors.email} />

                                        {profile.mustVerifyEmail && !profile.hasVerifiedEmail ? (
                                            <div className="mt-4 rounded-2xl border border-amber-500/20 bg-amber-500/10 p-4 text-sm text-amber-900 dark:text-amber-100">
                                                <div className="flex items-center gap-2 font-medium">
                                                    <MailCheck className="size-4" />
                                                    Your email address is unverified.
                                                </div>
                                                <p className="mt-2 leading-6">
                                                    You can resend the verification email from here without leaving the
                                                    profile page.
                                                </p>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    className="mt-3"
                                                    onClick={() => router.post(urls.sendVerification)}
                                                >
                                                    Re-send verification email
                                                </Button>

                                                {flash?.status === 'verification-link-sent' ? (
                                                    <p className="mt-3 text-sm">
                                                        A new verification link has been sent to your email address.
                                                    </p>
                                                ) : null}
                                            </div>
                                        ) : null}
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <Button type="submit" disabled={profileForm.processing}>
                                            Save changes
                                        </Button>
                                        {flash?.status === 'profile-updated' ? (
                                            <p className="text-sm text-muted-foreground">Saved.</p>
                                        ) : null}
                                    </div>
                                </form>
                            </section>

                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <h2 className="text-xl font-semibold text-card-foreground">Update password</h2>
                                <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                    Ensure your account is using a long, random password to stay secure.
                                </p>

                                <form onSubmit={savePassword} className="mt-6 space-y-5">
                                    <div>
                                        <label className="block text-sm font-medium text-foreground" htmlFor="current_password">
                                            Current password
                                        </label>
                                        <Input
                                            id="current_password"
                                            type="password"
                                            value={passwordForm.data.current_password}
                                            onChange={(event) => passwordForm.setData('current_password', event.target.value)}
                                            className="mt-2"
                                            autoComplete="current-password"
                                        />
                                        <FieldError message={updatePasswordErrors.current_password} />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-foreground" htmlFor="password">
                                            New password
                                        </label>
                                        <Input
                                            id="password"
                                            type="password"
                                            value={passwordForm.data.password}
                                            onChange={(event) => passwordForm.setData('password', event.target.value)}
                                            className="mt-2"
                                            autoComplete="new-password"
                                        />
                                        <FieldError message={updatePasswordErrors.password} />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-foreground" htmlFor="password_confirmation">
                                            Confirm password
                                        </label>
                                        <Input
                                            id="password_confirmation"
                                            type="password"
                                            value={passwordForm.data.password_confirmation}
                                            onChange={(event) => passwordForm.setData('password_confirmation', event.target.value)}
                                            className="mt-2"
                                            autoComplete="new-password"
                                        />
                                        <FieldError message={updatePasswordErrors.password_confirmation} />
                                    </div>

                                    <div className="flex items-center gap-4">
                                        <Button type="submit" disabled={passwordForm.processing}>
                                            Save password
                                        </Button>
                                        {flash?.status === 'password-updated' ? (
                                            <p className="text-sm text-muted-foreground">Saved.</p>
                                        ) : null}
                                    </div>
                                </form>
                            </section>
                        </div>

                        <section className="mt-6 rounded-[28px] border border-destructive/20 bg-card p-6 shadow-sm">
                            <div className="flex items-start gap-3">
                                <div className="mt-1 rounded-2xl bg-destructive/10 p-3 text-destructive">
                                    <ShieldAlert className="size-5" />
                                </div>
                                <div>
                                    <h2 className="text-xl font-semibold text-card-foreground">Delete account</h2>
                                    <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                                        Once your account is deleted, all of its resources and data will be permanently
                                        deleted. Enter your password to confirm that you want to permanently remove it.
                                    </p>
                                </div>
                            </div>

                            <form onSubmit={deleteAccount} className="mt-6 max-w-md space-y-5">
                                <div>
                                    <label className="block text-sm font-medium text-foreground" htmlFor="delete_password">
                                        Password
                                    </label>
                                    <Input
                                        id="delete_password"
                                        type="password"
                                        value={deletionForm.data.password}
                                        onChange={(event) => deletionForm.setData('password', event.target.value)}
                                        className="mt-2"
                                        placeholder="Password"
                                    />
                                    <FieldError message={userDeletionErrors.password} />
                                </div>

                                <Button type="submit" variant="destructive" disabled={deletionForm.processing}>
                                    <Trash2 className="size-4" />
                                    Delete account
                                </Button>
                            </form>
                        </section>
                    </div>
                </SidebarInset>
            </SidebarProvider>
        </>
    );
}
