import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { TrackedCTA } from '@/components/tracking/TrackedCTA';
import { TrackedForm } from '@/components/tracking/TrackedForm';
import type { LeadResponse } from '@/components/tracking/TrackedForm';

export default function FormDemo({
    paymentMode,
    productName,
    productPrice,
}: {
    paymentMode: string;
    productName: string;
    productPrice: number;
}) {
    const [error, setError] = useState('');
    const success = (response: LeadResponse) =>
        window.location.assign(response.redirect_url);

    return (
        <main className="bg-stone-50 text-slate-950">
            <Head title="Form Demo" />
            <section
                id="hero"
                className="grid min-h-screen place-items-center bg-indigo-950 px-6 text-center text-white"
            >
                <div className="max-w-3xl">
                    <p className="text-sm font-semibold tracking-[.25em] text-indigo-300 uppercase">
                        PBM Boilerplate · Form
                    </p>
                    <h1 className="mt-4 text-5xl font-black tracking-tight sm:text-7xl">
                        Form, attribution, dan payment sudah terhubung.
                    </h1>
                    <p className="mx-auto mt-6 max-w-xl text-indigo-100">
                        Mode aktif: {paymentMode}. Harga selalu dibaca server.
                    </p>
                    <TrackedCTA
                        className="mt-8 inline-flex rounded-xl bg-amber-300 px-6 py-3 font-bold text-indigo-950"
                        zone="hero"
                        action="form_anchor"
                        label="Daftar Sekarang"
                        href="#pricing"
                    >
                        Daftar Sekarang
                    </TrackedCTA>
                </div>
            </section>
            <section
                id="pricing"
                className="grid min-h-screen place-items-center px-6 py-20"
            >
                <div className="grid w-full max-w-4xl gap-10 md:grid-cols-2">
                    <div>
                        <p className="font-semibold text-indigo-700">
                            {productName}
                        </p>
                        <h2 className="mt-2 text-4xl font-black">
                            {new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                maximumFractionDigits: 0,
                            }).format(productPrice)}
                        </h2>
                        <p className="mt-4 text-slate-600">
                            Ketik pertama mengirim Form Start satu kali. Submit
                            menyimpan Lead di server.
                        </p>
                    </div>
                    <TrackedForm
                        formName="main"
                        onSuccess={success}
                        onError={setError}
                        className="space-y-4 rounded-2xl bg-white p-6 shadow-xl"
                    >
                        <label className="block text-sm font-medium">
                            Nama
                            <input
                                name="name"
                                required
                                className="mt-1 w-full rounded-lg border px-3 py-2"
                            />
                        </label>
                        <label className="block text-sm font-medium">
                            Email
                            <input
                                name="email"
                                type="email"
                                className="mt-1 w-full rounded-lg border px-3 py-2"
                            />
                        </label>
                        <label className="block text-sm font-medium">
                            WhatsApp
                            <input
                                name="phone"
                                required
                                className="mt-1 w-full rounded-lg border px-3 py-2"
                            />
                        </label>
                        <label className="block text-sm font-medium">
                            Kota (contoh extra field)
                            <input
                                name="city"
                                className="mt-1 w-full rounded-lg border px-3 py-2"
                            />
                        </label>
                        {error && (
                            <p role="alert" className="text-sm text-red-600">
                                {error}
                            </p>
                        )}
                        <button
                            type="submit"
                            className="w-full rounded-xl bg-indigo-700 px-5 py-3 font-bold text-white"
                        >
                            Kirim Form
                        </button>
                    </TrackedForm>
                </div>
            </section>
            <section
                id="faq"
                className="grid min-h-[50vh] place-items-center bg-stone-100 px-6 text-center"
            >
                <h2 className="text-3xl font-bold">
                    Section View aktif otomatis
                </h2>
            </section>
        </main>
    );
}
