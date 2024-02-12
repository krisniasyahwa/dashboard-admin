<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Stock Rollback') }}
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
                    { data: 'stock_rollback_at', name: 'Stock Rollback', render: function (data) {
                        return data != null ? `<span>✅ @ ${data}</span>` : `<span>❌</span>`;
                    },width: '20%'},
                    { data: 'merchant.name', name: 'merchant.name' },
                    { data: 'user.name', name: 'user.name' },
                    { data: 'status_payment', name: 'status_payment' },
                    { data: 'created_at', name: 'created_at'},
                    { data: 'updated_at', name: 'updated_at'},
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        width: '15%',
                    },
                ],
            });
        </script>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h2 class="p-4 bg-yellow-200 text-red-500 my-4 font-bold rounded-xl" >⚠️ Showing Rejected & Expired transaction only</h2>
            {{-- <div class="mb-10">
                <a href="{{ route('dashboard.merchant.create') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow-lg">
                    + Create Merchant
                </a>
            </div> --}}
            <div class="shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 bg-white sm:p-6">
                    <table id="crudTable">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Stock Rollback</th>
                            <th>Merchant</th>
                            <th>User</th>
                            <th>Status Payment</th>
                            <th>Created_at</th>
                            <th>Updated_at</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
