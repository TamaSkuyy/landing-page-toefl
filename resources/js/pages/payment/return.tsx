import { Head, Link } from '@inertiajs/react';

type Order = {
    order_number: string;
    name: string;
    amount: number;
    status: string;
    paid_at: string | null;
} | null;

export default function PaymentReturn({ order }: { order: Order }) {
    const status = order?.status ?? 'unknown';

    return (
        <main className="grid min-h-screen place-items-center bg-slate-950 p-6 text-white">
            <Head title="Payment Status" />
            <section className="w-full max-w-lg rounded-2xl border border-slate-800 bg-slate-900 p-8 text-center">
                <p className="text-sm font-semibold tracking-widest text-cyan-400 uppercase">
                    Payment Status
                </p>
                <h1 className="mt-3 text-3xl font-bold">
                    {status === 'paid'
                        ? 'Pembayaran berhasil'
                        : status === 'failed'
                          ? 'Pembayaran gagal'
                          : 'Pembayaran sedang diproses'}
                </h1>
                {order && (
                    <div className="mt-6 space-y-2 text-sm text-slate-300">
                        <p>Order: {order.order_number}</p>
                        <p>
                            Nominal:{' '}
                            {new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                maximumFractionDigits: 0,
                            }).format(order.amount)}
                        </p>
                    </div>
                )}
                <Link
                    href="/"
                    className="mt-8 inline-flex rounded-lg bg-cyan-400 px-5 py-3 font-semibold text-slate-950"
                >
                    Kembali
                </Link>
            </section>
        </main>
    );
}
