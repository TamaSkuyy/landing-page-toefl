import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';

type Order = {
    id: number;
    order_number: string;
    name: string;
    email: string | null;
    phone: string;
    amount: number;
    payment_method: string | null;
    status: string;
    paid_at: string | null;
    created_at: string;
};
type Pagination = {
    data: Order[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export default function Orders({
    orders,
    filters,
}: {
    orders: Pagination;
    filters: { search?: string; status?: string };
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const filter = (status = filters.status ?? '') =>
        router.get(
            '/admin/orders',
            { search, status },
            { preserveState: true },
        );
    const markPaid = (order: Order) =>
        router.post(
            `/admin/orders/${order.id}/mark-paid`,
            {},
            { preserveScroll: true },
        );

    return (
        <AdminLayout>
            <Head title="Orders" />
            <div className="space-y-6 p-6">
                <header>
                    <h1 className="text-2xl font-bold">Orders</h1>
                    <p className="text-sm text-muted-foreground">
                        Rekonsiliasi transaksi payment internal.
                    </p>
                </header>
                <Card>
                    <CardHeader>
                        <CardTitle>Filter</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        <input
                            className="rounded-md border bg-background px-3 py-2"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            onKeyDown={(event) =>
                                event.key === 'Enter' && filter()
                            }
                            placeholder="Order, nama, email, telepon"
                        />
                        <select
                            className="rounded-md border bg-background px-3 py-2"
                            value={filters.status ?? ''}
                            onChange={(event) => filter(event.target.value)}
                        >
                            <option value="">Semua status</option>
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                        </select>
                        <button
                            className="rounded-md border px-3 py-2"
                            onClick={() => filter()}
                        >
                            Cari
                        </button>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="overflow-x-auto pt-6">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left">
                                    <th className="p-2">Order</th>
                                    <th className="p-2">Customer</th>
                                    <th className="p-2">Amount</th>
                                    <th className="p-2">Status</th>
                                    <th className="p-2">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {orders.data.map((order) => (
                                    <tr key={order.id} className="border-b">
                                        <td className="p-2 font-mono">
                                            {order.order_number}
                                        </td>
                                        <td className="p-2">
                                            <div>{order.name}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {order.email ?? order.phone}
                                            </div>
                                        </td>
                                        <td className="p-2">
                                            {new Intl.NumberFormat(
                                                'id-ID',
                                            ).format(order.amount)}
                                        </td>
                                        <td className="p-2 capitalize">
                                            {order.status}
                                        </td>
                                        <td className="p-2">
                                            {order.status !== 'paid' && (
                                                <button
                                                    className="rounded border px-2 py-1"
                                                    onClick={() =>
                                                        markPaid(order)
                                                    }
                                                >
                                                    Mark paid
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        {orders.data.length === 0 && (
                            <p className="py-8 text-center text-muted-foreground">
                                Belum ada order.
                            </p>
                        )}
                        <div className="mt-4 flex justify-between">
                            <button
                                disabled={!orders.prev_page_url}
                                onClick={() =>
                                    orders.prev_page_url &&
                                    router.get(orders.prev_page_url)
                                }
                                className="rounded border px-3 py-1 disabled:opacity-40"
                            >
                                Sebelumnya
                            </button>
                            <span>
                                {orders.current_page} / {orders.last_page}
                            </span>
                            <button
                                disabled={!orders.next_page_url}
                                onClick={() =>
                                    orders.next_page_url &&
                                    router.get(orders.next_page_url)
                                }
                                className="rounded border px-3 py-1 disabled:opacity-40"
                            >
                                Berikutnya
                            </button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
