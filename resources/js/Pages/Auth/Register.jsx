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

export default function Register({ urls }) {
    const form = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function submit(event) {
        event.preventDefault();
        form.post(urls.register);
    }

    return (
        <>
            <Head title="Register" />

            <AuthLayout
                title="Create your account"
                description="Set up access to the app so you can connect WhatsApp, import history, and configure reply providers."
                footer={
                    <div className="text-sm text-muted-foreground">
                        Already registered?{' '}
                        <Link href={urls.login} className="underline-offset-4 hover:text-foreground hover:underline">
                            Log in
                        </Link>
                    </div>
                }
            >
                <form onSubmit={submit} className="space-y-5">
                    <div>
                        <label className="block text-sm font-medium text-foreground" htmlFor="name">
                            Name
                        </label>
                        <Input
                            id="name"
                            type="text"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            className="mt-2"
                            required
                            autoFocus
                            autoComplete="name"
                        />
                        <FieldError message={form.errors.name} />
                    </div>

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
                        Register
                    </Button>
                </form>
            </AuthLayout>
        </>
    );
}
