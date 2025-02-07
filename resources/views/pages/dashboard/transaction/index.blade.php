<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transaction') }}
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
                    { data: 'user.name', name: 'user.name' },
                    { data: 'total_price', name: 'total_price' },
                    { data: 'transaction_type', name: 'transaction_type' },
                    { data: 'payment_type', name: 'payment_type' },
                    { data: 'payment', name: 'payment' },
                    { data: 'status_payment', name: 'status_payment' },
                    { data: 'status', name: 'status' },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        width: '25%'
                    },
                ],
            });
        </script>
    </x-slot>

    <div class="py-2">
        <div class="mx-auto sm:px-6 lg:px-8" style="max-width: 100rem">
            <div class="shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 bg-white sm:p-6">
                    <table id="crudTable">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th style="text-align: left;">Nama</th>
                            <th>Total Harga</th>
                            <th>Transaction Type</th>
                            <th>Payment Type</th>
                            <th>Payment Method</th>
                            <th>Status Payment</th>
                            <th>Status Order</th>
                            <th>Aksi</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
