<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a class=" hover:underline" href="{{route('dashboard.transaction.index')}}">Transaction</a> &raquo; #{{ $transaction->id }} {{ $transaction->name }}
        </h2>
    </x-slot>

    <x-slot name="script">
        <script>
            // AJAX DataTable
            var datatable = $('#crudTable').DataTable({
                ajax: {
                    url: '{!! url()->current() !!}',
                },
                columns: [
                    { data: 'id', name: 'id', width: '5%'},
                    { data: 'products_id', name: 'products_id', width: '5%'},
                    { data: 'product.name', name: 'product.name' },
                    { data: 'product.price', name: 'product.price' },
                    { data: 'quantity', name: 'quantity' },
                    { data: 'note', name: 'note', width: '50%'},
                ],
            });
        </script>
    </x-slot>

    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="font-semibold text-lg text-gray-800 leading-tight mb-4">Transaction Details</h2>

            <div class="flex bg-white overflow-hidden shadow sm:rounded-lg mb-4">
                 <div class="p-6 bg-white border-b border-gray-200 w-1/2">
                    <table class="table-auto w-full">
                        <tbody>
                            <tr>
                                <th class="border px-6 py-2 text-right">Name</th>
                                <td class="border px-6 py-2">{{ $transaction->user->name }}</td>
                            </tr>
                            <tr>
                                <th class="border px-6 py-2 text-right">Email</th>
                                <td class="border px-6 py-2">{{ $transaction->user->email }}</td>
                            </tr>
                            <tr>
                                <th class="border px-6 py-2 text-right">Address</th>
                                <td class="border px-6 py-2">{{ $transaction->address }}</td>
                            </tr>
                            <tr>
                                <th class="border px-6 py-2 text-right">Total Price</th>
                                <td class="border px-6 py-2">{{ number_format($transaction->total_price) }}</td>
                            </tr>
                            <tr>
                                <th class="border px-6 py-2 text-right">Shipping Price</th>
                                <td class="border px-6 py-2">{{ number_format($transaction->shipping_price) }}</td>
                            </tr>
                            <tr>
                                <th class="border px-6 py-2 text-right">Transaction Type</th>
                                <td class="border px-6 py-2">{{ $transaction->transaction_type }}</td>
                            </tr>
                            <tr>
                                <th class="border px-6 py-2 text-right">Payment Method</th>
                                <td class="border px-6 py-2">{{ $transaction->payment }}</td>
                            </tr>
                            <tr>
                                <th class="border px-6 py-2 text-right">Payment Type </th>
                                <td class="border px-6 py-2">{{ $transaction->payment_type }}</td>
                            </tr>
                            <tr>
                                <th class="border px-6 py-2 text-right">Status Payment</th>
                                <td class="border px-6 py-2">{{ $transaction->status_payment }}</td>
                            </tr>
                            <tr>
                                <th class="border px-6 py-2 text-right">Status Order</th>
                                <td class="border px-6 py-2">{{ $transaction->status }}</td>
                            </tr>
                            <tr>
                                <th class="border px-6 py-2 text-right">Action</th>
                                <td class="border px-6 py-2">
                                    <a class="inline-block border border-gray-700 bg-gray-700 text-white rounded-md px-8 py-1 m-1 transition duration-500 ease select-none hover:bg-gray-800 focus:outline-none focus:shadow-outline"
                                        href="{{route('dashboard.transaction.edit', ['transaction' => $transaction->id, 'previous_page' => $previous_page ?? 'show'])}}" onclick="storeScrollPosition()">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-6 bg-white border-b border-gray-200 w-1/2">
                    <h3 class="font-semibold text-lg text-gray-800 leading-tight mb-4"> Bukti Transaksi QRIS</h3>
                    <img class="max-h-96" src="{{ $transaction->payment_image ?? 'https://upload.wikimedia.org/wikipedia/commons/thumb/c/c1/Empty_set_symbol.svg/640px-Empty_set_symbol.svg.png'}}" alt="">
                </div>
            </div>

            <h2 class="font-semibold text-lg text-gray-800 leading-tight mb-2">Transaction Items</h2>
            <div class="shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 bg-white sm:p-6">
                    <table id="crudTable">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>ID_P</th>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Qty</th>
                            <th>Notes</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Run the setScrollPosition function when the page loads
        $(document).ready(function() {
            setScrollPosition();
            clearScrollPosition()
        });
    </script>
</x-app-layout>
