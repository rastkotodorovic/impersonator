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

export default function ConfirmPassword({ urls }) {
    const form = useForm({
        password: '',
    });

    function submit(event) {
        event.preventDefault();
        form.post(urls.submit);
    }

    return (
        <>
            <Head title="Confirm Password" />

            <AuthLayout
                title="Confirm your password"
                description="This is a secure area of the application. Please confirm your password before continuing."
                footer={
                    <Link href={urls.dashboard} className="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline">
                        Back to dashboard
                    </Link>
                }
            >
                <form onSubmit={submit} className="space-y-5">
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
                            autoFocus
                            autoComplete="current-password"
                        />
                        <FieldError message={form.errors.password} />
                    </div>

                    <Button type="submit" className="w-full" disabled={form.processing}>
                        Confirm
                    </Button>
                </form>
            </AuthLayout>
        </>
    );
}
