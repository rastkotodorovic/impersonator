import { Head, router } from '@inertiajs/react';

import { AuthLayout } from '@/components/auth-layout';
import { Button } from '@/components/ui/button';

export default function VerifyEmail({ urls, flash }) {
    return (
        <>
            <Head title="Verify Email" />

            <AuthLayout
                title="Verify your email"
                description="Before getting started, verify your email address by clicking the link we emailed you. If you did not receive it, we can send another."
            >
                {flash?.status === 'verification-link-sent' ? (
                    <div className="mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                        A new verification link has been sent to the email address you provided during registration.
                    </div>
                ) : null}

                <div className="flex flex-col gap-3 sm:flex-row">
                    <Button type="button" className="flex-1" onClick={() => router.post(urls.resend)}>
                        Resend verification email
                    </Button>
                    <Button type="button" variant="outline" className="flex-1" onClick={() => router.post(urls.logout)}>
                        Log out
                    </Button>
                </div>
            </AuthLayout>
        </>
    );
}
