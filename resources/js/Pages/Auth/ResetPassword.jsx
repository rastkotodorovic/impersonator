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

export default function ResetPassword({ token, email, urls }) {
    const form = useForm({
        token,
        email: email ?? '',
        password: '',
        password_confirmation: '',
    });

    function submit(event) {
        event.preventDefault();
        form.post(urls.submit);
    }

    return (
        <>
            <Head title="Reset Password" />

            <AuthLayout
                title="Choose a new password"
                description="Finish the password reset flow by confirming your email address and setting a new password."
                footer={
                    <Link href={urls.login} className="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">
                        Back to login
                    </Link>
                }
            >
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
                            autoComplete="new-password"
                        />
                        <FieldError message={form.errors.password} />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-foreground" htmlFor="password_confirmation">
                            Confirm password
                        </label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            value={form.data.password_confirmation}
                            onChange={(event) => form.setData('password_confirmation', event.target.value)}
                            className="mt-2"
                            required
                            autoComplete="new-password"
                        />
                        <FieldError message={form.errors.password_confirmation} />
                    </div>

                    <Button type="submit" className="w-full" disabled={form.processing}>
                        Reset password
                    </Button>
                </form>
            </AuthLayout>
        </>
    );
}
