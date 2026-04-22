import { Head, Link, useForm } from '@inertiajs/react';

import { AuthLayout } from '@/components/auth-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

function FieldError({ message }) {
    if (!message) {
        return null;
    }

    return <p className="mt-2 text-sm text-destructive">{message}</p>;
}

export default function Login({ urls, flash }) {
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function submit(event) {
        event.preventDefault();
        form.post(urls.login);
    }

    return (
        <>
            <Head title="Log In" />

            <AuthLayout
                title="Welcome back"
                description="Sign in to continue managing WhatsApp connections, imports, and AI settings."
                footer={
                    <div className="flex items-center justify-between gap-4">
                        <Link href={urls.register} className="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">
                            Create an account
                        </Link>
                        <Link href={urls.forgotPassword} className="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">
                            Forgot password?
                        </Link>
                    </div>
                }
            >
                {flash?.status ? (
                    <div className="mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                        {flash.status}
                    </div>
                ) : null}

                <form onSubmit={submit} className="space-y-5">
                    <div>
                        <label className="block text-sm font-medium text-foreground" htmlFor="email">
                            Email
                        </label>
                        <Input
                            id="email"
                            type="email"
                            value={form.data.email}
                            onChange={(event) => form.setData('email', event.target.value)}
                            className="mt-2"
                            required
                            autoFocus
                            autoComplete="username"
                        />
                        <FieldError message={form.errors.email} />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-foreground" htmlFor="password">
                            Password
                        </label>
                        <Input
                            id="password"
                            type="password"
                            value={form.data.password}
                            onChange={(event) => form.setData('password', event.target.value)}
                            className="mt-2"
                            required
                            autoComplete="current-password"
                        />
                        <FieldError message={form.errors.password} />
                    </div>

                    <label className="flex items-center gap-3 text-sm text-muted-foreground">
                        <input
                            type="checkbox"
                            checked={form.data.remember}
                            onChange={(event) => form.setData('remember', event.target.checked)}
                            className="rounded border-border text-primary focus:ring-primary"
                        />
                        Remember me
                    </label>

                    <Button type="submit" className="w-full" disabled={form.processing}>
                        Log in
                    </Button>
                </form>
            </AuthLayout>
        </>
    );
}
