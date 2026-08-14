import { useForm } from '@inertiajs/react';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';

export default function LoginPage({ action, icon: Icon, title, subtitle, dataInterface }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
    });

    function handleSubmit(e) {
        e?.preventDefault?.();
        post(action);
    }

    return (
        <div data-interface={dataInterface} className="min-h-screen bg-background flex items-center justify-center p-6">
            <form onSubmit={handleSubmit} className="w-full max-w-[380px]">
                <Card className="bg-card shadow-none">
                    <CardHeader className="text-center pb-2">
                        <div className="mx-auto mb-3 flex size-14 items-center justify-center rounded-2xl bg-primary/10">
                            <Icon className="size-7 text-primary" />
                        </div>
                        <CardTitle className="text-xl">{title}</CardTitle>
                        <CardDescription>
                            {subtitle}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        {errors.email && (
                            <div className="rounded-xl border border-destructive/50 bg-destructive/10 p-3 text-sm text-destructive">
                                {errors.email}
                            </div>
                        )}

                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-medium text-foreground">Email</label>
                            <input
                                type="email"
                                value={data.email}
                                onChange={e => setData('email', e.target.value)}
                                placeholder="kasir@w9cafe.com"
                                autoComplete="email"
                                className="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground outline-none transition-colors focus:border-primary/50 focus:ring-2 focus:ring-primary/20"
                            />
                        </div>

                        <div className="flex flex-col gap-1.5">
                            <label className="text-sm font-medium text-foreground">Kata Sandi</label>
                            <input
                                type="password"
                                value={data.password}
                                onChange={e => setData('password', e.target.value)}
                                placeholder="Masukkan kata sandi..."
                                autoComplete="current-password"
                                className="h-11 w-full rounded-lg border border-input bg-background px-3.5 text-sm text-foreground outline-none transition-colors focus:border-primary/50 focus:ring-2 focus:ring-primary/20"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex h-11 w-full items-center justify-center rounded-lg bg-primary text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
                        >
                            {processing ? 'Memproses...' : 'Masuk'}
                        </button>
                    </CardContent>
                </Card>
            </form>
        </div>
    );
}
