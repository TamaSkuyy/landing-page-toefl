import { Head, Link } from '@inertiajs/react';

export default function ThankYou() {
    return (
        <main className="grid min-h-screen place-items-center bg-emerald-950 p-6 text-white">
            <Head title="Terima Kasih" />
            <section className="max-w-lg text-center">
                <p className="text-sm font-bold tracking-widest text-emerald-300 uppercase">
                    Lead tersimpan
                </p>
                <h1 className="mt-4 text-5xl font-black">Terima kasih.</h1>
                <p className="mt-4 text-emerald-100">
                    Halaman ini adalah placeholder. Ganti dengan instruksi
                    berikutnya untuk klien.
                </p>
                <Link
                    href="/"
                    className="mt-8 inline-flex rounded-lg bg-emerald-300 px-5 py-3 font-bold text-emerald-950"
                >
                    Kembali ke demo
                </Link>
            </section>
        </main>
    );
}
