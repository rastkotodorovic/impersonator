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

export default function ForgotPassword({ urls, flash }) {
    const form = useForm({
        email: '',
    });

    function submit(event) {
        event.preventDefault();
        form.post(urls.submit);
    }

    return (
        <>
            <Head title="Forgot Password" />

            <AuthLayout
                title="Reset your password"
                description="Enter your email address and we will send you a reset link so you can choose a new password."
                footer={
                    <Link href={urls.login} className="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">
                        Back to login
                    </Link>
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
                        />
                        <FieldError message={form.errors.email} />
                    </div>

                    <Button type="submit" className="w-full" disabled={form.processing}>
                        Email password reset link
                    </Button>
                </form>
            </AuthLayout>
        </>
    );
}
